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

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractCaptchaMethod
 *
 * Shared site-verify logic for Turnstile / reCAPTCHA v2. A failed or skipped
 * verification is reported as spam so it flows through the same
 * SpamShieldValidator pipeline as the honeypot/blacklist/link checks.
 */
abstract class AbstractCaptchaMethod extends AbstractMethod
{
    /**
     * @return string e.g. 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
     */
    abstract protected function getVerifyUrl(): string;

    /**
     * Name of the POST field the widget injects into the form (e.g. 'cf-turnstile-response')
     */
    abstract protected function getResponseFieldName(): string;

    /**
     * Fail-closed: a missing token or a failed/unreachable site-verify call is treated as spam.
     *
     * @param string $secret
     * @return bool true if spam recognized (verification failed)
     */
    protected function verify(string $secret): bool
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $parsedBody = $request?->getParsedBody();
        $token = is_array($parsedBody) ? ($parsedBody[$this->getResponseFieldName()] ?? '') : '';

        if (empty($token)) {
            return true;
        }

        $formParams = [
            'secret' => $secret,
            'response' => $token,
        ];
        $remoteAddress = $request?->getAttribute('normalizedParams')?->getRemoteAddress();
        if (!empty($remoteAddress)) {
            $formParams['remoteip'] = $remoteAddress;
        }

        try {
            $response = GeneralUtility::makeInstance(RequestFactory::class)->request(
                $this->getVerifyUrl(),
                'POST',
                ['form_params' => $formParams]
            );
            $result = json_decode((string)$response->getBody(), true);
            return empty($result['success']);
        } catch (\Throwable $e) {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(static::class)
                ->warning('Captcha site-verify call failed: ' . $e->getMessage());
            return true;
        }
    }
}
