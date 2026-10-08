<?php

declare(strict_types=1);

namespace Qc\QcComments\SpamShield\Methods;

/***
 *
 * This file is part of Qc Comments project.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2023 <techno@quebec.ca>
 *
 ***/

use Qc\QcComments\Domain\Model\Comment;

/**
 * Class RecaptchaMethod
 *
 * Verifies the reCAPTCHA v2 token server-side via siteverify.
 * Only runs when Turnstile is not enabled, mirroring Show.html which only ever
 * renders one widget (Turnstile takes priority over reCAPTCHA).
 */
class RecaptchaMethod extends AbstractCaptchaMethod
{
    protected function getVerifyUrl(): string
    {
        return 'https://www.google.com/recaptcha/api/siteverify';
    }

    protected function getResponseFieldName(): string
    {
        return 'g-recaptcha-response';
    }

    /**
     * @return bool true if spam recognized (missing/failed reCAPTCHA verification)
     */
    public function spamCheck(Comment $comment): bool
    {
        if (empty($this->settings['recaptcha']['enabled']) || !empty($this->settings['turnstile']['enabled'])) {
            return false;
        }

        return $this->verify((string)($this->settings['recaptcha']['secret'] ?? ''));
    }
}
