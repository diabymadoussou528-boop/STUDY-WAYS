<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeWebhookService $webhookService): Response
    {
        try {
            $webhookService->handle(
                $request->all(),
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (HttpException $exception) {
            return response($exception->getMessage(), $exception->getStatusCode());
        } catch (\Throwable $exception) {
            Log::error('Stripe webhook processing failed', [
                'error' => $exception->getMessage(),
            ]);

            return response('Processing failed', 500);
        }

        return response('OK', 200);
    }
}
