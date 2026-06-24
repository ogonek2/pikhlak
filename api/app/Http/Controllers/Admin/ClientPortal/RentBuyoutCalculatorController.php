<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Project;
use App\Services\Rental\RentBuyoutCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentBuyoutCalculatorController extends Controller
{
    public function __construct(
        private readonly RentBuyoutCalculator $calculator,
    ) {}

    public function preview(Request $request): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $data = $request->validate([
            'car_id' => ['required', 'exists:cars,id'],
            'first_payment' => ['required', 'numeric', 'min:0'],
            'term_years' => ['required', 'integer', 'min:1', 'max:15'],
            'overpayment_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $car = Car::query()
            ->where('project_id', $project->id)
            ->findOrFail($data['car_id']);

        try {
            $result = $this->calculator->calculate([
                'car_price' => $car->price,
                'first_payment' => $data['first_payment'],
                'term_years' => $data['term_years'],
                'overpayment_rate' => $data['overpayment_rate'] ?? null,
                'currency' => $car->currency,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $result,
            'car' => [
                'id' => $car->id,
                'title' => $car->title(),
                'price' => (float) $car->price,
                'currency' => $car->currency,
            ],
            'summary' => $this->calculator->formatAdminSummary($result),
        ]);
    }
}
