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
 * Class TurnstileMethod
 *
 * Verifies the Cloudflare Turnstile token server-side via siteverify.
 */
class TurnstileMethod extends AbstractCaptchaMethod
{
    protected function getVerifyUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }

    protected function getResponseFieldName(): string
    {
        return 'cf-turnstile-response';
    }

    /**
     * @return bool true if spam recognized (missing/failed Turnstile verification)
     */
    public function spamCheck(Comment $comment): bool
    {
        if (empty($this->settings['turnstile']['enabled'])) {
            return false;
        }

        return $this->verify((string)($this->settings['turnstile']['secret'] ?? ''));
    }
}
