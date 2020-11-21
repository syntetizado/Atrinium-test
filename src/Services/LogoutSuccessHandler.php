<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    protected $httpUtils;
    protected $targetUrl;

    /**
     * @param HttpUtils $httpUtils
     */
    public function __construct(HttpUtils $httpUtils)
    {
        $this->httpUtils = $httpUtils;

        $this->targetUrl = '/?logout=success';
    }

    /**
     * {@inheritdoc}
     */
    public function onLogoutSuccess(Request $request)
    {
        $response = $this->httpUtils->createRedirectResponse($request, $this->targetUrl);

        return $response;
    }
}
