<?php

namespace Omnipay\Tami\Message;

use JsonException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    protected $response;

    protected $request;

    protected $data;

    public function __construct(RequestInterface $request, $data)
    {
        parent::__construct($request, $data);

        $this->request = $request;
        $this->response = $data;

        if ($data instanceof ResponseInterface) {
            $body = (string) $data->getBody();

            try {
                $this->data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $this->data = [
                    'success' => false,
                    'errorMessage' => $body,
                ];
            }
        } elseif (is_array($data)) {
            $this->data = $data;
        }
    }

    public function isSuccessful(): bool
    {
        if ($this->isRedirect()) {
            return false;
        }

        return isset($this->data['success']) && $this->data['success'] === true;
    }

    public function isRedirect(): bool
    {
        return isset($this->data['success'])
            && $this->data['success'] === true
            && isset($this->data['threeDSHtmlContent']);
    }

    public function getRedirectUrl()
    {
        return null;
    }

    public function getRedirectMethod(): string
    {
        return 'POST';
    }

    public function getRedirectData()
    {
        if ($this->isRedirect()) {
            return $this->data;
        }

        return null;
    }

    /**
     * Get the decoded 3DS HTML content for redirect.
     */
    public function getRedirectHtml(): ?string
    {
        if (isset($this->data['threeDSHtmlContent'])) {
            return base64_decode($this->data['threeDSHtmlContent']);
        }

        return null;
    }

    /**
     * Tami returns ready-to-render HTML rather than a URL to redirect to. The
     * default Omnipay form-builder is useless here, so override and return the
     * decoded bank-side HTML directly.
     */
    public function getRedirectResponse()
    {
        if (! $this->isRedirect()) {
            throw new \Omnipay\Common\Exception\RuntimeException('This response does not support redirection.');
        }

        return new HttpResponse((string) $this->getRedirectHtml());
    }

    public function getMessage(): ?string
    {
        return $this->data['errorMessage'] ?? null;
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['bankReferenceNumber'] ?? null;
    }

    public function getData(): ?array
    {
        return $this->data;
    }
}
