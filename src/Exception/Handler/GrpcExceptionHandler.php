<?php
/**
 * Class MyGrpcExceptionHandler
 * 作者: su
 * 时间: 2020/11/18 17:47
 * 备注:
 */

namespace Chive\Exception\Handler;


use Chive\Exception\BusinessException;
use Chive\Helper\ErrorHelper;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Pb\Reply;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Google\Protobuf\Internal\Message;
use Hyperf\Grpc\Parser;

class GrpcExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $message = new Reply();
        $message->setErrCode(ErrorHelper::FAIL_CODE);
        $message->setMsg('网络错误');
        if ($throwable instanceof BusinessException || $throwable instanceof ValidationException) {
            $message->setErrCode($throwable->getCode());
            $message->setMsg($throwable->getMessage());
            return $this->handleResponse($message, $response);
        }

        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));

        //return $this->transferToResponse($throwable->getCode(), $throwable->getMessage(), $response);
        return $this->handleResponse($message, $response);
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    /**
     * Handle GRPC Response.
     * @param int $httpStatus
     */
    protected function handleResponse(?Message $message, ResponseInterface $response, $httpStatus = 200, string $grpcStatus = '0', string $grpcMessage = ''): ResponseInterface
    {
        return $response->withStatus($httpStatus)
            ->withBody(new SwooleStream(Parser::serializeMessage($message)))
            ->withAddedHeader('Server', 'Hyperf')
            ->withAddedHeader('Content-Type', 'application/grpc')
            ->withAddedHeader('trailer', 'grpc-status, grpc-message')
            ->withTrailer('grpc-status', $grpcStatus)
            ->withTrailer('grpc-message', $grpcMessage);
    }
}