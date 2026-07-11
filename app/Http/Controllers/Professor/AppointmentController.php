<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AppointmentController extends Controller
{
    public function index(): View
    {
        $professor = auth()->user();

        $appointments = Appointment::query()
            ->where('professor_id', $professor->id)
            ->with(['student:id,name,avatar,email', 'course:id,title'])
            ->latest('scheduled_at')
            ->get();

        return view('professor.appointments', compact('appointments'));
    }

    public function accept(Request $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $this->authorize('respond', $appointment);

        $validated = $request->validate([
            'response_note' => ['nullable', 'string', 'max:500'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
        ]);

        try {
            $service->accept(
                $appointment,
                auth()->user(),
                $validated['response_note'] ?? null,
                $validated['meeting_link'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Rendez-vous accepté.');
    }

    public function reject(Request $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $this->authorize('respond', $appointment);

        $validated = $request->validate(['response_note' => ['nullable', 'string', 'max:500']]);

        try {
            $service->reject($appointment, auth()->user(), $validated['response_note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Rendez-vous refusé.');
    }

    public function reschedule(Request $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $this->authorize('respond', $appointment);

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'response_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->reschedule(
                $appointment,
                auth()->user(),
                $validated['scheduled_at'],
                $validated['response_note'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Nouveau créneau proposé.');
    }
}
