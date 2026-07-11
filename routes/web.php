<?php

use App\Http\Controllers\Admin\AiRecommendationController;
use App\Http\Controllers\Admin\ApprovalRequestController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CourseManagementController;
use App\Http\Controllers\Admin\EnrollmentManagementController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StudentManagementController;
use App\Http\Controllers\Admin\TeacherManagementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseLearnController;
use App\Http\Controllers\CoursePageController;
use App\Http\Controllers\CourseSearchController;
use App\Http\Controllers\FirebaseTestController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PlatformNotificationController;
use App\Http\Controllers\Professor\AppointmentController as ProfessorAppointmentController;
use App\Http\Controllers\Professor\CourseWorkflowController;
use App\Http\Controllers\Professor\MessagingController as ProfessorMessagingController;
use App\Http\Controllers\Professor\ProfessorDashboardController;
use App\Http\Controllers\Professor\QuizController as ProfessorQuizController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\Student\AiTutorController;
use App\Http\Controllers\Student\AppointmentController as StudentAppointmentController;
use App\Http\Controllers\Student\CertificateController;
use App\Http\Controllers\Student\CourseCheckoutController;
use App\Http\Controllers\Student\EnrollmentController;
use App\Http\Controllers\Student\LearningProgressController;
use App\Http\Controllers\Student\MessagingController as StudentMessagingController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\SubscriptionController;
use App\Http\Controllers\SuperAdmin\SimpleAdminController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Models\Course;
use App\Models\Testimonial;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $testimonials = Testimonial::approved()->with('user')->latest()->limit(8)->get();
    $featuredCourses = Course::query()
        ->published()
        ->with(['user:id,name', 'category:id,name'])
        ->withCount('enrollments')
        ->withAvg('reviews', 'rating')
        ->latest('published_at')
        ->limit(6)
        ->get();
    $courseCount = Course::query()->published()->count();

    return view('home', compact('testimonials', 'featuredCourses', 'courseCount'));
})->name('home');

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| DASHBOARD REDIRECTION
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    $user = Auth::user();

    if (! $user) {
        return redirect('/login');
    }

    return match ($user->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'professor' => redirect()->route('professor.dashboard'),
        default => redirect()->route('student.dashboard'),
    };
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])
        ->name('profile.avatar.update');

    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])
        ->name('profile.avatar.destroy');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| TESTIMONIALS (Public + Auth)
|--------------------------------------------------------------------------
*/

Route::get('/testimonials', [TestimonialController::class, 'index'])
    ->name('testimonials.index');

Route::get('/certificates/verify/{token}', [CertificateVerificationController::class, 'show'])
    ->name('certificates.verify');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])
    ->name('seo.sitemap');

Route::get('/courses/search', [CourseSearchController::class, 'index'])
    ->name('courses.search');
Route::get('/courses/search/preview', [CourseSearchController::class, 'preview'])
    ->name('courses.search.preview');

Route::middleware('auth')->group(function () {
    Route::get('/search', SearchController::class)->name('search');
    Route::get('/payments/{payment}/receipt', [ReceiptController::class, 'show'])
        ->name('payments.receipt');
});

Route::middleware('auth')->group(function () {
    Route::get('/testimonials/create', [TestimonialController::class, 'create'])
        ->name('testimonials.create');

    Route::post('/testimonials', [TestimonialController::class, 'store'])
        ->name('testimonials.store');
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin', 'password.changed'])
    ->prefix('admin')
    ->group(function () {

        Route::get('/dashboard', [AdminController::class, 'index'])
            ->name('admin.dashboard');

        Route::get('/students', [StudentManagementController::class, 'index'])
            ->name('admin.students');
        Route::get('/students/{student}', [StudentManagementController::class, 'show'])
            ->name('admin.students.show');
        Route::post('/students/{student}/toggle-status', [StudentManagementController::class, 'toggleStatus'])
            ->name('admin.students.toggle-status');
        Route::delete('/students/{student}', [StudentManagementController::class, 'destroy'])
            ->name('admin.students.destroy');

        Route::get('/teachers', [TeacherManagementController::class, 'index'])
            ->name('admin.teachers');
        Route::get('/teachers/{teacher}', [TeacherManagementController::class, 'show'])
            ->name('admin.teachers.show');
        Route::post('/teachers/{teacher}/toggle-status', [TeacherManagementController::class, 'toggleStatus'])
            ->name('admin.teachers.toggle-status');
        Route::delete('/teachers/{teacher}', [TeacherManagementController::class, 'destroy'])
            ->name('admin.teachers.destroy');

        Route::get('/courses', [CourseManagementController::class, 'index'])
            ->name('admin.courses');
        Route::post('/courses/{course}/status/{status}', [CourseManagementController::class, 'updateStatus'])
            ->name('admin.courses.status');
        Route::post('/courses/{course}/publish', [CourseManagementController::class, 'publish'])
            ->name('admin.courses.publish');
        Route::post('/courses/{course}/duplicate', [CourseManagementController::class, 'duplicate'])
            ->name('admin.courses.duplicate');
        Route::delete('/courses/{course}/manage', [CourseManagementController::class, 'destroy'])
            ->name('admin.courses.manage.destroy');

        Route::get('/enrollments', [EnrollmentManagementController::class, 'index'])
            ->name('admin.enrollments');

        Route::get('/ai-recommendations', [AiRecommendationController::class, 'index'])
            ->name('admin.ai');
        Route::get('/reports', [ReportController::class, 'index'])
            ->name('admin.reports');
        Route::get('/reports/export/{type}/{format}', [ReportController::class, 'export'])
            ->name('admin.reports.export');
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('admin.notifications');

        Route::middleware('superadmin')->group(function () {
            Route::get('/approvals', [ApprovalRequestController::class, 'index'])
                ->name('admin.approvals');
            Route::get('/approvals/{approval}', [ApprovalRequestController::class, 'show'])
                ->name('admin.approvals.show');
            Route::post('/approvals/{approval}/approve', [ApprovalRequestController::class, 'approve'])
                ->name('admin.approvals.approve');
            Route::post('/approvals/{approval}/reject', [ApprovalRequestController::class, 'reject'])
                ->name('admin.approvals.reject');
        });

        /*
        |--------------------------------------------------------------------------
        | ADMIN COURSES
        |--------------------------------------------------------------------------
        */

        Route::get('/courses/create', [CourseController::class, 'create'])
            ->name('admin.courses.create');

        Route::post('/courses/store', [CourseController::class, 'store'])
            ->name('admin.courses.store');

        Route::delete('/courses/{id}', [AdminController::class, 'deleteCourse'])
            ->name('admin.courses.delete');

        /*
        |--------------------------------------------------------------------------
        | ADMIN USERS
        |--------------------------------------------------------------------------
        */

        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])
            ->name('admin.users.delete');

        /*
        |--------------------------------------------------------------------------
        | ADMIN TESTIMONIALS
        |--------------------------------------------------------------------------
        */

        Route::get('/testimonials', [AdminController::class, 'testimonials'])
            ->name('admin.testimonials');

        Route::delete('/testimonials/{id}', [AdminController::class, 'deleteTestimonial'])
            ->name('admin.testimonials.delete');

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN ONLY — Manage Admins
        |--------------------------------------------------------------------------
        */

        Route::middleware('superadmin')->group(function () {

            Route::get('/admins', [SimpleAdminController::class, 'index'])
                ->name('admin.admins');

            Route::post('/admins', [SimpleAdminController::class, 'store'])
                ->name('admin.admins.store');

            Route::put('/admins/{admin}', [SimpleAdminController::class, 'update'])
                ->name('admin.admins.update');

            Route::post('/admins/{admin}/toggle-status', [SimpleAdminController::class, 'toggleStatus'])
                ->name('admin.admins.toggle-status');

            Route::delete('/admins/{admin}', [SimpleAdminController::class, 'destroy'])
                ->name('admin.admins.destroy');

            Route::post('/admins/{admin}/temporary-password', [SimpleAdminController::class, 'sendTemporaryPassword'])
                ->name('admin.admins.temporary-password');

            Route::post('/admins/{admin}/reset-link', [SimpleAdminController::class, 'sendResetLink'])
                ->name('admin.admins.reset-link');

            Route::post('/admins/{admin}/logout', [SimpleAdminController::class, 'forceLogout'])
                ->name('admin.admins.logout');

            Route::get('/audit-logs', [AuditLogController::class, 'index'])
                ->name('admin.audit-logs');
            Route::get('/audit-logs/export/{format}', [AuditLogController::class, 'export'])
                ->name('admin.audit-logs.export');
        });
    });

/*
|--------------------------------------------------------------------------
| PROFESSOR ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:professor'])
    ->prefix('professor')
    ->group(function () {

        Route::get('/dashboard', [ProfessorDashboardController::class, 'index'])
            ->name('professor.dashboard');

        Route::get('/courses', [ProfessorDashboardController::class, 'courses'])
            ->name('professor.courses.index');
        Route::get('/students', [ProfessorDashboardController::class, 'students'])
            ->name('professor.students');
        Route::get('/messages', [ProfessorMessagingController::class, 'index'])
            ->name('professor.messages');
        Route::get('/messages/thread', [ProfessorMessagingController::class, 'thread'])
            ->name('professor.messages.thread');
        Route::post('/messages/send', [ProfessorMessagingController::class, 'send'])
            ->name('professor.messages.send');
        Route::get('/messages/unread', [ProfessorMessagingController::class, 'unreadCount'])
            ->name('professor.messages.unread');
        Route::get('/appointments', [ProfessorAppointmentController::class, 'index'])
            ->name('professor.appointments');
        Route::post('/appointments/{appointment}/accept', [ProfessorAppointmentController::class, 'accept'])
            ->name('professor.appointments.accept');
        Route::post('/appointments/{appointment}/reject', [ProfessorAppointmentController::class, 'reject'])
            ->name('professor.appointments.reject');
        Route::post('/appointments/{appointment}/reschedule', [ProfessorAppointmentController::class, 'reschedule'])
            ->name('professor.appointments.reschedule');
        Route::get('/reviews', [ProfessorDashboardController::class, 'reviews'])
            ->name('professor.reviews');

        /*
        |--------------------------------------------------------------------------
        | COURSES
        |--------------------------------------------------------------------------
        */

        Route::get('/courses/create', [CourseController::class, 'create'])
            ->name('courses.create');

        Route::post('/courses/store', [CourseController::class, 'store'])
            ->name('courses.store');

        Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])
            ->name('courses.edit');

        Route::put('/courses/{course}', [CourseController::class, 'update'])
            ->name('courses.update');

        Route::post('/courses/{course}/submit-review', [CourseWorkflowController::class, 'submitForReview'])
            ->name('professor.courses.submit-review');

        Route::get('/quizzes', [ProfessorQuizController::class, 'index'])
            ->name('professor.quizzes.index');
        Route::get('/courses/{course}/quizzes/create', [ProfessorQuizController::class, 'create'])
            ->name('professor.quizzes.create');
        Route::post('/courses/{course}/quizzes', [ProfessorQuizController::class, 'store'])
            ->name('professor.quizzes.store');
        Route::get('/quizzes/{quiz}/attempts', [ProfessorQuizController::class, 'attempts'])
            ->name('professor.quizzes.attempts');
        Route::get('/quizzes/attempts/{attempt}', [ProfessorQuizController::class, 'showAttempt'])
            ->name('professor.quizzes.attempts.show');
        Route::post('/quizzes/answers/{answer}/grade', [ProfessorQuizController::class, 'gradeAnswer'])
            ->name('professor.quizzes.answers.grade');

        Route::delete('/courses/{id}', [CourseController::class, 'destroy'])
            ->name('courses.delete');

        /*
        |--------------------------------------------------------------------------
        | LESSONS
        |--------------------------------------------------------------------------
        */

        Route::get('/lessons/create/{course}', [LessonController::class, 'create'])
            ->name('lessons.create');

        Route::post('/lessons/store', [LessonController::class, 'store'])
            ->name('lessons.store');
    });

/*
|--------------------------------------------------------------------------
| STUDENT ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->group(function () {

        Route::get('/dashboard', [StudentDashboardController::class, 'index'])
            ->name('student.dashboard');
        Route::get('/courses', [StudentDashboardController::class, 'courses'])
            ->name('student.courses');
        Route::get('/certificates', [CertificateController::class, 'index'])
            ->name('student.certificates.index');
        Route::get('/certificates/{enrollment}', [CertificateController::class, 'show'])
            ->name('student.certificates.show');
        Route::get('/progress', [LearningProgressController::class, 'index'])
            ->name('student.progress');
        Route::get('/quizzes', [StudentQuizController::class, 'index'])
            ->name('student.quizzes.index');
        Route::get('/quizzes/attempts/{attempt}', [StudentQuizController::class, 'result'])
            ->name('student.quizzes.result');
        Route::get('/quizzes/{quiz}', [StudentQuizController::class, 'show'])
            ->name('student.quizzes.show');
        Route::post('/quizzes/{quiz}/submit', [StudentQuizController::class, 'submit'])
            ->name('student.quizzes.submit');
        Route::get('/ai-tutor', [AiTutorController::class, 'index'])
            ->name('student.ai-tutor');
        Route::post('/ai-tutor/chat', [AiTutorController::class, 'chat'])
            ->middleware('premium')
            ->name('student.ai-tutor.chat');
        Route::get('/ai-tutor/history', [AiTutorController::class, 'history'])
            ->name('student.ai-tutor.history');
        Route::delete('/ai-tutor/clear', [AiTutorController::class, 'clear'])
            ->name('student.ai-tutor.clear');
        Route::get('/premium', [SubscriptionController::class, 'checkout'])
            ->name('student.premium');
        Route::post('/premium/subscribe', [SubscriptionController::class, 'subscribe'])
            ->name('student.premium.subscribe');
        Route::post('/premium/cancel', [SubscriptionController::class, 'cancel'])
            ->name('student.premium.cancel');
        Route::post('/premium/renew', [SubscriptionController::class, 'renew'])
            ->name('student.premium.renew');
        Route::get('/premium/history', [SubscriptionController::class, 'history'])
            ->name('student.premium.history');

        Route::get('/checkout/course/{course}', [CourseCheckoutController::class, 'show'])
            ->name('student.checkout.course');
        Route::post('/checkout/course/{course}', [CourseCheckoutController::class, 'pay'])
            ->name('student.checkout.pay');
        Route::get('/checkout/success/{payment}', [CourseCheckoutController::class, 'success'])
            ->name('student.checkout.success');
        Route::get('/premium/success/{payment}', [SubscriptionController::class, 'success'])
            ->name('student.premium.success');

        Route::get('/enrollment/{course}/confirm', [EnrollmentController::class, 'confirm'])
            ->name('student.enrollment.confirm');
        Route::post('/enrollment/{course}', [EnrollmentController::class, 'store'])
            ->name('student.enrollment.store');
        Route::get('/enrollment/success/{enrollment}', [EnrollmentController::class, 'success'])
            ->name('student.enrollment.success');
        Route::delete('/enrollment/{enrollment}', [EnrollmentController::class, 'destroy'])
            ->name('student.enrollment.cancel');

        Route::get('/appointments', [StudentAppointmentController::class, 'index'])
            ->name('student.appointments');
        Route::post('/appointments', [StudentAppointmentController::class, 'store'])
            ->name('student.appointments.store');
        Route::post('/appointments/{appointment}/cancel', [StudentAppointmentController::class, 'cancel'])
            ->name('student.appointments.cancel');

        Route::get('/messages', [StudentMessagingController::class, 'index'])
            ->name('student.messages');
        Route::get('/messages/thread', [StudentMessagingController::class, 'thread'])
            ->name('student.messages.thread');
        Route::post('/messages/send', [StudentMessagingController::class, 'send'])
            ->name('student.messages.send');
        Route::get('/messages/unread', [StudentMessagingController::class, 'unreadCount'])
            ->name('student.messages.unread');
    });

/*
|--------------------------------------------------------------------------
| PUBLIC COURSES
|--------------------------------------------------------------------------
*/

Route::get('/catalogue', [CatalogController::class, 'index'])
    ->name('catalog.index');

Route::get('/courses/{course}', [CourseController::class, 'show'])
    ->name('courses.show');

Route::get('/courses/{course}/learn/{lesson?}', [CourseLearnController::class, 'show'])
    ->name('courses.learn');

Route::middleware('auth')->group(function () {
    Route::post('/courses/{course}/lessons/{lesson}/complete', [CourseLearnController::class, 'complete'])
        ->name('courses.lessons.complete');
});

Route::get('/learn/{slug}', [CoursePageController::class, 'show'])
    ->name('learn.show');

/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/notifications', [PlatformNotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('/notifications/{notification}/read', [PlatformNotificationController::class, 'markRead'])
        ->name('notifications.read');
    Route::post('/notifications/read-all', [PlatformNotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');

    Route::post('/courses/{id}/rate', [ReviewController::class, 'store'])
        ->name('courses.rate');
});

Route::get('/firebase-test', [FirebaseTestController::class, 'index']);

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('webhooks.stripe');
