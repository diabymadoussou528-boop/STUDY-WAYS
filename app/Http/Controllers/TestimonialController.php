<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    /**
     * Show the public testimonials index (for homepage AJAX or page).
     */
    public function index(): View
    {
        $testimonials = Testimonial::approved()
            ->with('user')
            ->latest()
            ->get();

        return view('testimonials.index', compact('testimonials'));
    }

    /**
     * Show the form for creating a new testimonial.
     */
    public function create(): View
    {
        return view('testimonials.create');
    }

    /**
     * Store a new testimonial submitted by an authenticated user.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'min:20', 'max:1000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        Testimonial::create([
            'user_id' => auth()->id(),
            'message' => $request->message,
            'rating' => $request->rating,
            'is_approved' => true,
        ]);

        return redirect()->route('home')
            ->with('success', 'Merci pour votre témoignage ! Il sera visible sur la page d\'accueil.');
    }

    /**
     * Delete a testimonial (admin only).
     */
    public function destroy(int $id): RedirectResponse
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->delete();

        return back()->with('success', 'Témoignage supprimé avec succès.');
    }
}
