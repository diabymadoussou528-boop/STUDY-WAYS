<form method="POST" action="{{ route('courses.delete', $course->id) }}">
    @csrf
    @method('DELETE')
    <button>🗑️ Supprimer</button>
</form>
