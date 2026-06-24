<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Admin\ClientPortal\Concerns\NotifiesRentalClient;
use App\Http\Controllers\Admin\ClientPortal\Concerns\RespondsWithClientProfile;
use App\Http\Controllers\Controller;
use App\Models\RentalClient;
use App\Models\RentalClientContract;
use App\Models\RentalClientInsurance;
use App\Models\RentalClientMaintenance;
use App\Models\RentalClientPayment;
use App\Models\RentalClientPhone;
use App\Models\RentalClientVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RentalClientNestedController extends Controller
{
    use NotifiesRentalClient;
    use RespondsWithClientProfile;

    public function storePhone(Request $request, RentalClient $client): RedirectResponse|JsonResponse
    {
        $this->ensureProjectClient($request, $client);
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:40'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_primary')) {
            $client->phones()->update(['is_primary' => false]);
        }

        $phone = $client->phones()->create([
            'label' => $data['label'] ?? 'mobile',
            'phone' => $data['phone'],
            'is_primary' => $request->boolean('is_primary'),
        ]);

        $this->notifyClient($client, 'phone.added', [], $phone);

        return $this->clientProfileResponse($request, $client, 'Телефон добавлен.');
    }

    public function destroyPhone(Request $request, RentalClient $client, RentalClientPhone $phone): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $phone);

        $phone->delete();

        return $this->clientProfileResponse($request, $client, 'Телефон удалён.');
    }

    public function storeVehicle(Request $request, RentalClient $client): RedirectResponse|JsonResponse
    {
        $this->ensureProjectClient($request, $client);
        $data = $this->validateVehicle($request);

        if ($request->boolean('is_current')) {
            $client->vehicles()->update(['is_current' => false]);
        }

        $vehicle = $client->vehicles()->create($data);

        $this->notifyClient($client, 'vehicle.created', [], $vehicle);

        return $this->clientProfileResponse($request, $client, 'Автомобиль добавлен.');
    }

    public function updateVehicle(Request $request, RentalClient $client, RentalClientVehicle $vehicle): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $vehicle);
        $data = $this->validateVehicle($request);

        if ($request->boolean('is_current')) {
            $client->vehicles()->where('id', '!=', $vehicle->id)->update(['is_current' => false]);
        }

        $vehicle->update($data);
        $vehicle->refresh();

        $this->notifyClient($client, 'vehicle.updated', [], $vehicle);

        return $this->clientProfileResponse($request, $client, 'Автомобиль обновлён.');
    }

    public function destroyVehicle(Request $request, RentalClient $client, RentalClientVehicle $vehicle): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $vehicle);
        $vehicle->delete();

        return $this->clientProfileResponse($request, $client, 'Автомобиль удалён.');
    }

    public function storeContract(Request $request, RentalClient $client): RedirectResponse|JsonResponse
    {
        $this->ensureProjectClient($request, $client);
        $contract = $client->contracts()->create($this->validateContract($request));

        $this->notifyClient($client, 'contract.created', [], $contract);

        return $this->clientProfileResponse($request, $client, 'Договор добавлен.');
    }

    public function updateContract(Request $request, RentalClient $client, RentalClientContract $contract): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $contract);
        $contract->update($this->validateContract($request));
        $contract->refresh();

        $this->notifyClient($client, 'contract.updated', [], $contract);

        return $this->clientProfileResponse($request, $client, 'Договор обновлён.');
    }

    public function destroyContract(Request $request, RentalClient $client, RentalClientContract $contract): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $contract);
        $contract->delete();

        return $this->clientProfileResponse($request, $client, 'Договор удалён.');
    }

    public function storePayment(Request $request, RentalClient $client): RedirectResponse|JsonResponse
    {
        $this->ensureProjectClient($request, $client);
        $payment = $client->payments()->create($this->validatePayment($request));

        $this->notifyClient($client, 'payment.created', [], $payment, [
            'event_date' => $payment->due_date->toDateString(),
        ]);

        return $this->clientProfileResponse($request, $client, 'Платёж добавлен.');
    }

    public function updatePayment(Request $request, RentalClient $client, RentalClientPayment $payment): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $payment);
        $payment->update($this->validatePayment($request));
        $payment->refresh();

        $this->notifyClient($client, 'payment.updated', [], $payment, [
            'event_date' => $payment->due_date->toDateString(),
        ]);

        return $this->clientProfileResponse($request, $client, 'Платёж обновлён.');
    }

    public function destroyPayment(Request $request, RentalClient $client, RentalClientPayment $payment): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $payment);
        $payment->delete();

        return $this->clientProfileResponse($request, $client, 'Платёж удалён.');
    }

    public function markPaymentPaid(Request $request, RentalClient $client, RentalClientPayment $payment): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $payment);
        $payment->update([
            'status' => 'paid',
            'paid_at' => now()->toDateString(),
        ]);
        $payment->refresh();

        $this->notifyClient($client, 'payment.paid', [], $payment, [
            'event_date' => $payment->due_date->toDateString(),
        ]);

        return $this->clientProfileResponse($request, $client, 'Платёж отмечен оплаченным.');
    }

    public function storeInsurance(Request $request, RentalClient $client): RedirectResponse|JsonResponse
    {
        $this->ensureProjectClient($request, $client);
        $insurance = $client->insurances()->create($this->validateInsurance($request));

        $this->notifyClient($client, 'insurance.created', [], $insurance, [
            'event_date' => $insurance->valid_until?->toDateString() ?? now()->toDateString(),
        ]);

        return $this->clientProfileResponse($request, $client, 'Страховка добавлена.');
    }

    public function updateInsurance(Request $request, RentalClient $client, RentalClientInsurance $insurance): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $insurance);
        $insurance->update($this->validateInsurance($request));
        $insurance->refresh();

        $this->notifyClient($client, 'insurance.updated', [], $insurance, [
            'event_date' => $insurance->valid_until?->toDateString() ?? now()->toDateString(),
        ]);

        return $this->clientProfileResponse($request, $client, 'Страховка обновлена.');
    }

    public function destroyInsurance(Request $request, RentalClient $client, RentalClientInsurance $insurance): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $insurance);
        $insurance->delete();

        return $this->clientProfileResponse($request, $client, 'Страховка удалена.');
    }

    public function storeMaintenance(Request $request, RentalClient $client): RedirectResponse|JsonResponse
    {
        $this->ensureProjectClient($request, $client);
        $maintenance = $client->maintenances()->create($this->validateMaintenance($request));
        $maintenance->load('vehicle');

        $this->notifyClient($client, 'maintenance.created', [], $maintenance, [
            'event_date' => $maintenance->scheduled_at?->toDateString() ?? now()->toDateString(),
        ]);

        return $this->clientProfileResponse($request, $client, 'Запись ТО добавлена.');
    }

    public function updateMaintenance(Request $request, RentalClient $client, RentalClientMaintenance $maintenance): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $maintenance);
        $maintenance->update($this->validateMaintenance($request));
        $maintenance->load('vehicle');

        $this->notifyClient($client, 'maintenance.updated', [], $maintenance, [
            'event_date' => $maintenance->scheduled_at?->toDateString() ?? now()->toDateString(),
        ]);

        return $this->clientProfileResponse($request, $client, 'Запись ТО обновлена.');
    }

    public function destroyMaintenance(Request $request, RentalClient $client, RentalClientMaintenance $maintenance): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($request, $client, $maintenance);
        $maintenance->delete();

        return $this->clientProfileResponse($request, $client, 'Запись ТО удалена.');
    }

    /** @return array<string, mixed> */
    private function validateVehicle(Request $request): array
    {
        return $request->validate([
            'make' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'color' => ['nullable', 'string', 'max:40'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'vin' => ['nullable', 'string', 'max:40'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'is_current' => ['sometimes', 'boolean'],
        ]) + ['is_current' => $request->boolean('is_current', true)];
    }

    /** @return array<string, mixed> */
    private function validateContract(Request $request): array
    {
        return $request->validate([
            'rental_client_vehicle_id' => ['nullable', 'exists:rental_client_vehicles,id'],
            'contract_number' => ['nullable', 'string', 'max:60'],
            'rent_start' => ['required', 'date'],
            'rent_end' => ['nullable', 'date', 'after_or_equal:rent_start'],
            'monthly_amount' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'buyout_option' => ['sometimes', 'boolean'],
            'status' => ['required', 'in:active,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]) + ['buyout_option' => $request->boolean('buyout_option')];
    }

    /** @return array<string, mixed> */
    private function validatePayment(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:rent,insurance,service,other'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,paid,overdue,cancelled'],
            'week_number' => ['nullable', 'integer', 'min:0'],
            'period_index' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateInsurance(Request $request): array
    {
        return $request->validate([
            'provider' => ['required', 'string', 'max:120'],
            'policy_number' => ['nullable', 'string', 'max:80'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'premium_amount' => ['nullable', 'numeric', 'min:0'],
            'coverage_notes' => ['nullable', 'string'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateMaintenance(Request $request): array
    {
        return $request->validate([
            'rental_client_vehicle_id' => ['nullable', 'exists:rental_client_vehicles,id'],
            'type' => ['required', 'in:service,oil_change,inspection,other'],
            'title' => ['required', 'string', 'max:160'],
            'scheduled_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'mileage_at' => ['nullable', 'integer', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:planned,scheduled,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function ensureProjectClient(Request $request, RentalClient $client): void
    {
        if ($client->project_id !== $request->attributes->get('project')->id) {
            abort(404);
        }
    }

    private function ensureOwned(Request $request, RentalClient $client, object $model): void
    {
        $this->ensureProjectClient($request, $client);

        if ((int) $model->rental_client_id !== (int) $client->id) {
            abort(404);
        }
    }
}
