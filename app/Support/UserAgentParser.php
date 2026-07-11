<?php

namespace App\Support;

class UserAgentParser
{
    /**
     * @return array{browser: ?string, operating_system: ?string, device: ?string}
     */
    public static function parse(?string $userAgent): array
    {
        if (! $userAgent) {
            return ['browser' => null, 'operating_system' => null, 'device' => null];
        }

        $browser = 'Unknown';
        if (preg_match('/Edg\/([\d.]+)/', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome\/([\d.]+)/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/([\d.]+)/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/([\d.]+)/', $userAgent) && ! str_contains($userAgent, 'Chrome')) {
            $browser = 'Safari';
        }

        $os = 'Unknown';
        if (preg_match('/Windows NT/', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Android/', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = 'iOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        }

        $device = 'Desktop';
        if (preg_match('/Mobile|Android|iPhone/', $userAgent)) {
            $device = 'Mobile';
        } elseif (preg_match('/iPad|Tablet/', $userAgent)) {
            $device = 'Tablet';
        }

        return [
            'browser' => $browser,
            'operating_system' => $os,
            'device' => $device,
        ];
    }
}
