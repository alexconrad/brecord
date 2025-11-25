<?php

declare(strict_types=1);

namespace Bilo\Middleware;

use Bilo\Enum\EnvVar;
use Bilo\Service\ConfigService;
use Fig\Http\Message\StatusCodeInterface;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Throwable;

class OpenApiValidationMiddleware implements MiddlewareInterface
{
    private string $specPath;

    public function __construct(
        private readonly ConfigService $configService,
        private readonly LoggerInterface $logger,
        string $specPath,
    ) {
        $this->specPath = $specPath;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        // Only validate requests that have api/v1 in their path
        $path = $request->getUri()->getPath();
        if (!str_contains($path, 'api/v1')) {
            return $handler->handle($request);
        }

        try {
            /**
             * In ValidatorBuilder We can use ->setCache( instance of CacheItemPoolInterface)
             * @see \Psr\Cache\CacheItemPoolInterface
             */
            $validator = new ValidatorBuilder();
            if (!$this->configService->getRequired(EnvVar::DEBUG)) {
                $validator->setCache(new ApcuAdapter('openapi', 60));
            }
            $validator->fromYamlFile($this->specPath);
            $validator = $validator->getServerRequestValidator();

            $validator->validate($request);
            return $handler->handle($request);

        } catch (ValidationFailed $exception) {
            $response = new Response();
            /** @noinspection JsonEncodingApiUsageInspection */
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    $exception->getMessage()
                ],
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST);
        } catch (Throwable $exception) {
            $this->logger->error(get_class($exception)."|".$exception->getMessage()."|".$exception->getPrevious()?->getMessage(), ['exception' => $exception]);
            // Handle any other errors (e.g., invalid spec file)
            $response = new Response();

            $info = [
                'success' => false,
                'message' => 'Application error',
            ];
            if ($this->configService->getRequired(EnvVar::DEBUG)) {
                $info['error1'] = $exception->getMessage();
                $info['error2'] = $exception->getPrevious()?->getMessage();
                $info['error3'] = $exception->getPrevious()?->getPrevious()?->getMessage();
            }
            $response->getBody()->write(json_encode($info));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
