<?php

return [

    'email_enabled' => env('LMS_EMAIL_NOTIFICATIONS', true),

    'email_types' => [
        'new_enrollment',
        'enrollment_confirmed',
        'payment_received',
        'premium_subscription',
        'quiz_completed',
        'quiz_graded',
        'quiz_pending_grading',
        'course_published',
        'course_approved',
        'approval_request',
        'appointment_request',
        'appointment_accepted',
        'appointment_rejected',
        'appointment_rescheduled',
        'appointment_cancelled',
    ],

];
