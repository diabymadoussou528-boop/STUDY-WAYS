# StudyWays Functional Specification

## Overview

StudyWays is a Laravel 13 e-learning platform built with PHP 8.3, Tailwind CSS, Alpine.js, and Vite. It uses Laravel Breeze-style authentication and Groq-powered AI chat for student tutoring.

The codebase is organized around role-based dashboards, course management, student/professor messaging, admin moderation, and a public course catalog.

## System Architecture

- Backend: Laravel MVC controllers, Eloquent models, service classes.
- Frontend: Blade templates, Tailwind CSS, Alpine.js, and JavaScript assets.
- Auth: Laravel auth with email verification, password reset, and a forced password change flow for admins.
- Storage: local file storage for avatars and uploaded course videos.
- AI: Groq chat completions configured in `config/ai.php`.
- Notifications: Laravel mail notifications for password reset, admin onboarding, and audit-related messages.

## User Roles

### Student

Students can access enrolled course content, use the AI tutor, message teachers, rate courses, and view premium access status.

Permissions:

- view student dashboard and enrolled course list
- use the AI tutor for enrolled courses
- message course professors
- submit testimonials and rate courses
- manage profile and avatar
- view premium page status

### Professor

Professors create and manage courses and lessons, view engagement, and communicate with enrolled students.

Permissions:

- view professor dashboard and course reports
- create, store, and delete courses
- add lessons to courses
- view enrolled students and student messages
- respond to course reviews
- access appointments data

### Admin

Admins manage users, courses, testimonials, reports, and platform notifications.

Permissions:

- view admin dashboard and analytics
- manage student and professor accounts
- moderate courses and testimonials
- view reports and export data
- view pending approvals and notifications

### Super Admin

Super admins have elevated admin account management and approval workflow authority.

Permissions:

- manage admin users
- toggle admin status
- delete admin accounts
- send temporary passwords and reset links
- force admin logout
- review approval requests

## Data Model Summary

### `users`

Fields include `name`, `email`, `password`, `role`, `phone`, `avatar`, `is_active`, `is_super_admin`, `first_login`, `is_premium`, `premium_plan`, and `email_verified_at`.

### `courses`

Fields include `title`, `description`, `slug`, `video_url`, `video_path`, `category_id`, `user_id`, `status`, and `views`.

### `lessons`

Fields include `course_id`, `title`, `content`, `video_url`, and `video_path`.

### `categories`

Fields include `name` and `slug`.

### `enrollments`

Fields include `user_id`, `course_id`, `progress`, `enrolled_at`, `last_accessed_at`, and `completed_at`.

### `reviews`

Fields include `user_id`, `course_id`, `rating`, and `comment`.

### `testimonials`

Fields include `user_id`, `message`, `rating`, and `is_approved`.

### `messages`

Fields include `from_user_id`, `to_user_id`, `course_id`, `body`, and `read_at`.

### `appointments`

Fields include `student_id`, `professor_id`, `course_id`, `scheduled_at`, `status`, `message`, and `response_note`.

### `ai_chat_messages`

Fields include `user_id`, `course_id`, `lesson_id`, `topic`, `role`, `content`, and `mode`.

### `admin_action_requests`

Fields include `requested_by`, `action`, `target_type`, `target_id`, `payload`, `title`, `description`, `status`, `reviewed_by`, `review_note`, and `reviewed_at`.

### `admin_audit_logs`

Fields include `user_id`, `action`, `target_user_id`, `metadata`, and `ip_address`.

## Core Features

### Authentication & Account Management

- Guest registration and login.
- Email verification with resend support.
- Password reset flow.
- Admin forced password change flow via `password/force-change`.
- Profile editing including avatar upload, avatar removal, and account deletion.
- Role middleware for `admin`, `professor`, and `student`.

### Public Platform

#### Landing Page

- Public homepage with approved testimonials and calls to action for registration/login.
- Displays curated testimonials pulled from the database.

#### Course Catalog and Discovery

- Public course listing and individual course detail pages.
- Course detail pages show title, description, instructor, and video content.
- Course detail `CourseController::show` increments view count.
- Courses can be built from video URLs or uploaded local videos.

#### Testimonials

- Authenticated users can submit testimonials.
- Testimonial submissions are stored and immediately marked as approved in current implementation.
- Admins can list and delete testimonials from the admin dashboard.

### Student Experience

#### Student Dashboard

- Displays enrolled courses, progress, recommended courses, recent messages, and activity.
- Shows premium status and recent student activity.
- Uses `StudentDashboardService` to compile enrollments, message previews, recommendations, and stats.

#### AI Tutor

- Student AI tutor page at `/student/ai-tutor`.
- Uses `AiTutorService` to fetch enrolled courses, chat history, and call Groq via `chat/completions`.
- Chat context includes course, lesson, topic, and mode.
- Supports history retrieval and clearing with `ai_chat_messages` persistence.
- Access is gated so only students enrolled in the course can ask course-specific questions.

#### Messaging

- Student-to-professor messaging threads scoped by course.
- Students can contact only the teachers of courses they are enrolled in.
- Conversations are grouped by course and other participant.
- Unread message count and thread list are available.
- Messages are sent via `MessagingService` with student/professor authorization checks.

#### Appointments

- The application has appointment records and views for students and professors.
- Current web routes expose appointment listing pages, while create/approval flows are not fully surfaced.

#### Premium Access

- Student premium page at `/student/premium`.
- Premium membership is stored via `is_premium` and `premium_plan`.
- Current implementation does not include an integrated checkout or payment processor.

### Professor Experience

#### Professor Dashboard

- Shows courses, enrolled student counts, rating averages, total views, pending appointments, recent messages, and reviews.
- Uses `ProfessorDashboardService` to aggregate course and engagement metrics.

#### Course Management

- Professors can create courses, including optional video URLs or local uploads.
- Professors can delete their own courses.
- Professors can add lessons to a course via lesson creation pages.

#### Student Management and Communication

- Professors can view students enrolled in their courses.
- Professors can message students in course-scoped threads.
- Professors can review recent course feedback and pending appointment requests.

### Admin Experience

#### Admin Dashboard

- Central admin dashboard with metrics, charts, notifications, and recent activity.
- Uses `AdminAnalyticsService` for hero stats, chart data, and platform metrics.
- Displays counts for students, professors, courses, views, admins, testimonials, pending approvals, and AI recommendations.

#### User Management

- Admins can list, inspect, toggle status, and delete student and teacher accounts.
- Super admins can manage admin accounts.

#### Course Management

- Admins can list courses, update course status, and delete courses.
- Admins can also create courses through admin course creation routes.

#### Testimonials Moderation

- Admin testimonials listing with delete actions.
- Admin can review testimonies and remove inappropriate content.

#### Notifications and Reports

- Notification feed for admin events.
- Reports page with export support for selected data types.

#### Approval Workflow

- Superadmin-only approval routes for admin action requests.
- Pending approvals are shown in admin dashboards and charts.

## Routes and Endpoint Summary

### Public

- `/` — home page
- `/register`, `/login` — auth
- `/forgot-password`, `/reset-password`, `/password/reset-success`
- `/verify-email`, `/email/verification-notification`
- `/testimonials`, `/testimonials/create`, `/testimonials`
- `/courses/{id}` — course details
- `/learn/{slug}` — hardcoded course playlist page
- `/courses/{id}/rate` — post course review/rating
- `/firebase-test` — internal/test route

### Student

- `/student/dashboard`
- `/student/courses`
- `/student/ai-tutor`
- `/student/ai-tutor/chat`
- `/student/ai-tutor/history`
- `/student/ai-tutor/clear`
- `/student/premium`
- `/student/messages`
- `/student/messages/thread`
- `/student/messages/send`
- `/student/messages/unread`
- `/student/appointments`

### Professor

- `/professor/dashboard`
- `/professor/courses`
- `/professor/students`
- `/professor/messages`
- `/professor/messages/thread`
- `/professor/messages/send`
- `/professor/messages/unread`
- `/professor/appointments`
- `/professor/reviews`
- `/professor/courses/create`
- `/professor/courses/store`
- `/professor/courses/{id}` delete
- `/professor/lessons/create/{course}`
- `/professor/lessons/store`

### Admin

- `/admin/dashboard`
- `/admin/students`, `/admin/students/{student}`
- `/admin/students/{student}/toggle-status`
- `/admin/students/{student}` delete
- `/admin/teachers`, `/admin/teachers/{teacher}`
- `/admin/teachers/{teacher}/toggle-status`
- `/admin/teachers/{teacher}` delete
- `/admin/courses`
- `/admin/courses/{course}/status/{status}`
- `/admin/courses/manage` delete
- `/admin/courses/create`
- `/admin/courses/store`
- `/admin/users/{id}` delete
- `/admin/ai-recommendations`
- `/admin/reports`
- `/admin/reports/export/{type}/{format}`
- `/admin/notifications`
- `/admin/testimonials`
- `/admin/testimonials/{id}` delete

### Super Admin

- `/admin/admins`
- `/admin/admins` POST create
- `/admin/admins/{admin}` PUT update
- `/admin/admins/{admin}/toggle-status`
- `/admin/admins/{admin}` delete
- `/admin/admins/{admin}/temporary-password`
- `/admin/admins/{admin}/reset-link`
- `/admin/admins/{admin}/logout`
- `/admin/approvals`
- `/admin/approvals/{approval}`
- `/admin/approvals/{approval}/approve`
- `/admin/approvals/{approval}/reject`

## Implementation Notes

- Course enrollment exists in the database and powers student dashboards, messaging, and AI tutor access, but public enrollment flow is not exposed as a dedicated web route.
- The `/learn/{slug}` route uses a hardcoded course map in `CoursePageController` and is not driven by the courses database.
- Premium membership is stored on users, but there is no payment/checkout integration in the codebase.
- Testimonials are currently auto-approved on submission.
- Appointments have listing pages but no fully visible student request or professor response endpoints in the current route set.
- Admin-created courses are available through admin routes in addition to professor course creation.
- `CourseController::show` increments course view counts on every view.

## Recommendations

1. Add explicit course enrollment and checkout/subscription flows.
2. Replace hardcoded `/learn/{slug}` playlists with database-driven course pages.
3. Expose appointment creation, response, and approval actions in routes and controllers.
4. Add course publishing state management and draft workflows for professors.
5. Expand premium gating to actual payment plans and feature restrictions.
6. Introduce audit logging for admin actions and support ticket workflows.

## Missing Features for a Complete Platform

- Payment gateway and subscription billing
- Course completion certificates
- Quizzes, assessments, and grading
- PWA/offline support
- Advanced search and filtering
- SEO metadata management
- Robust audit logging
- Personalized course recommendations
- Multi-language interface support

## Conclusion

StudyWays combines an LMS-style backend with a public catalog and modern admin controls. The current app already implements student AI tutoring, on-platform messaging, course creation, and admin analytics. Completing the platform requires enrollment flows, payment/premium delivery, and database-backed course playlists.
