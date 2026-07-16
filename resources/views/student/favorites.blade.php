@extends('layouts.student')

@section('title', 'Mes favoris')

@section('content')
<x-admin-page-header kicker="Découverte" title="Mes favoris" subtitle="Les cours que vous avez mis de côté pour plus tard." />

<section class="widget-card glass-card reveal-up">
    <div class="widget-header">
        <div>
            <h3 class="widget-title">Wishlist</h3>
            <p class="widget-subtitle">{{ $favorites->total() }} cours enregistré(s)</p>
        </div>
        <a href="{{ route('catalog.index') }}" class="btn btn-outline btn-sm">Explorer le catalogue</a>
    </div>
    <div class="widget-body">
        <div class="sw-courses-grid">
            @forelse($favorites as $course)
                <x-course-card :course="$course" cta-label="Voir les détails" />
            @empty
                <div class="empty-state premium-empty" style="grid-column:1/-1;">
                    <i class="fas fa-heart"></i>
                    <p>Aucun favori pour le moment. Ajoutez des cours depuis la page détail.</p>
                    <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-sm" style="margin-top:12px;">Parcourir les cours</a>
                </div>
            @endforelse
        </div>

        <div style="margin-top:24px;">
            <x-course-pagination :paginator="$favorites" />
        </div>
    </div>
</section>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/course-card.css') }}">
<link rel="stylesheet" href="{{ asset('css/course-experience.css') }}">
@endsection
