const todayStart = () => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
};

/** @param {{ status: string, due_date?: string }} payment */
export function effectivePaymentStatus(payment, today = todayStart()) {
    if (payment.status === 'paid' || payment.status === 'cancelled') {
        return payment.status;
    }
    if (payment.status === 'overdue') {
        return 'overdue';
    }
    if (payment.due_date) {
        const due = new Date(`${payment.due_date}T00:00:00`);
        if (due < today) {
            return 'overdue';
        }
    }

    return payment.status === 'pending' ? 'pending' : payment.status;
}

/** @param {Array<{ effectiveStatus: string }>} items */
export function aggregatePeriodStatus(items) {
    if (!items.length) {
        return 'pending';
    }
    if (items.every((p) => p.effectiveStatus === 'paid')) {
        return 'paid';
    }
    if (items.some((p) => p.effectiveStatus === 'overdue')) {
        return 'overdue';
    }
    if (items.some((p) => p.effectiveStatus === 'paid')) {
        return 'partial';
    }

    return 'pending';
}

/**
 * @param {Array<Record<string, unknown>>} payments
 * @param {number} periodWeeks
 */
export function groupPayments(payments, periodWeeks = 4) {
    const today = todayStart();

    const enriched = payments.map((p) => ({
        ...p,
        effectiveStatus: effectivePaymentStatus(p, today),
    }));

    const downPayment = enriched
        .filter((p) => p.week_number === 0)
        .sort((a, b) => String(a.due_date).localeCompare(String(b.due_date)));

    const weekly = enriched
        .filter((p) => p.week_number !== 0)
        .sort((a, b) => {
            if (a.week_number != null && b.week_number != null) {
                return a.week_number - b.week_number;
            }

            return String(a.due_date).localeCompare(String(b.due_date));
        });

    const periodMap = new Map();
    const orphans = [];

    for (const payment of weekly) {
        let periodIndex = null;

        if (payment.period_index != null && payment.period_index > 0) {
            periodIndex = payment.period_index;
        } else if (payment.week_number != null && payment.week_number > 0) {
            periodIndex = Math.ceil(payment.week_number / periodWeeks);
        }

        if (periodIndex == null) {
            orphans.push(payment);
            continue;
        }

        if (!periodMap.has(periodIndex)) {
            periodMap.set(periodIndex, []);
        }
        periodMap.get(periodIndex).push(payment);
    }

    if (orphans.length) {
        const existingKeys = [...periodMap.keys()];
        const base = existingKeys.length ? Math.max(...existingKeys) : 0;

        orphans.forEach((payment, i) => {
            const target = base + Math.floor(i / periodWeeks) + 1;
            if (!periodMap.has(target)) {
                periodMap.set(target, []);
            }
            periodMap.get(target).push(payment);
        });
    }

    const periods = [...periodMap.entries()]
        .sort(([a], [b]) => a - b)
        .map(([index, weeks]) => buildPeriodGroup(index, weeks, periodWeeks));

    const groups = [];

    if (downPayment.length) {
        groups.push({
            id: 'down',
            kind: 'down',
            label: 'Первый взнос',
            periodIndex: 0,
            weeks: downPayment,
            weekFrom: 0,
            weekTo: 0,
            total: sumAmounts(downPayment),
            paidTotal: sumPaid(downPayment),
            status: aggregatePeriodStatus(downPayment),
            dateFrom: downPayment[0]?.due_date,
            dateTo: downPayment[downPayment.length - 1]?.due_date,
        });
    }

    groups.push(...periods);

    const roadmap = groups.map((g) => ({
        id: g.id,
        kind: g.kind,
        label: g.label,
        status: g.status,
        paidCount: g.weeks.filter((w) => w.effectiveStatus === 'paid').length,
        totalCount: g.weeks.length,
    }));

    const stats = {
        totalPeriods: groups.length,
        paidPeriods: groups.filter((g) => g.status === 'paid').length,
        overduePeriods: groups.filter((g) => g.status === 'overdue').length,
        totalAmount: sumAmounts(enriched),
        paidAmount: sumPaid(enriched),
    };

    return { groups, roadmap, stats };
}

function buildPeriodGroup(index, weeks, periodWeeks) {
    const sorted = [...weeks].sort((a, b) => {
        if (a.week_number != null && b.week_number != null) {
            return a.week_number - b.week_number;
        }

        return String(a.due_date).localeCompare(String(b.due_date));
    });

    const weekNumbers = sorted.map((w) => w.week_number).filter((n) => n != null);
    const weekFrom = weekNumbers.length ? Math.min(...weekNumbers) : null;
    const weekTo = weekNumbers.length ? Math.max(...weekNumbers) : null;

    return {
        id: `p${index}`,
        kind: 'period',
        label: `Период ${index}`,
        periodIndex: index,
        weeks: sorted,
        weekFrom,
        weekTo,
        total: sumAmounts(sorted),
        paidTotal: sumPaid(sorted),
        status: aggregatePeriodStatus(sorted),
        dateFrom: sorted[0]?.due_date,
        dateTo: sorted[sorted.length - 1]?.due_date,
        expectedWeeks: periodWeeks,
    };
}

function sumAmounts(items) {
    return items.reduce((sum, p) => sum + Number(p.amount || 0), 0);
}

function sumPaid(items) {
    return items
        .filter((p) => p.effectiveStatus === 'paid')
        .reduce((sum, p) => sum + Number(p.amount || 0), 0);
}

export function periodWeeksFromContract(contracts = []) {
    const active = contracts.find((c) => c.status === 'active') ?? contracts[0];
    return active?.period_weeks ?? 4;
}
