<?php

namespace Modera\FileUploaderBundle\Tests\Unit\Controller;

use Modera\FileRepositoryBundle\Exceptions\FileValidationException;
use Modera\FileUploaderBundle\Controller\UniversalUploaderController;
use Modera\FileUploaderBundle\Uploading\WebUploader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2015 Modera Foundation
 */
class UniversalUploaderControllerTest extends \PHPUnit\Framework\TestCase
{
    private $container;

    /**
     * @var UniversalUploaderController
     */
    private $ctr;

    private $webUploader;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->container = \Phake::mock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->webUploader = \Phake::mock(WebUploader::class);

        $this->ctr = new UniversalUploaderController();
        $this->ctr->setContainer($this->container);
    }

    public function testUploadActionWhenNotEnabled()
    {
        $thrownException = null;
        try {
            $this->ctr->uploadAction(new Request());
        } catch (NotFoundHttpException $e) {
            $thrownException = $e;
        }

        $this->assertNotNull($thrownException);
        $this->assertEquals(404, $thrownException->getStatusCode());
    }

    private function teachContainer(Request $request, $isUploaderEnabled, $uploaderResult)
    {
        if ($uploaderResult instanceof \Exception) {
            \Phake::when($this->webUploader)
                ->upload($request)
                ->thenThrow($uploaderResult)
            ;
        } else {
            \Phake::when($this->webUploader)
                ->upload($request)
                ->thenReturn($uploaderResult)
            ;
        }

        \Phake::when($this->container)
            ->getParameter('modera_file_uploader.is_enabled')
            ->thenReturn($isUploaderEnabled)
        ;

        \Phake::when($this->container)
            ->get('modera_file_uploader.uploading.web_uploader')
            ->thenReturn($this->webUploader)
        ;
    }

    public function testUploadActionWhenNoUploadHandledRequest()
    {
        $request = new Request();

        $this->teachContainer($request, true, null);

        $response = $this->ctr->uploadAction($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $content);
        $this->assertFalse($content['success']);
        $this->assertArrayHasKey('error', $content);
        $this->assertContains('Unable', $content['error']);
    }

    public function testUploadActionSuccess()
    {
        $request = new Request();

        $result = array(
            'success' => true,
            'blah' => 'foo',
        );

        $this->teachContainer($request, true, new JsonResponse($result));

        $response = $this->ctr->uploadAction($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $this->assertSame($result, json_decode($response->getContent(), true));
    }

    public function testUploadActionWithValidationException()
    {
        $request = new Request();

        $exception = FileValidationException::create(new \SplFileInfo(__FILE__), ['some error']);

        $this->teachContainer($request, true, $exception);

        $response = $this->ctr->uploadAction($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $content);
        $this->assertFalse($content['success']);
        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('errors', $content);
        $this->assertTrue(is_array($content['errors']));
        $this->assertEquals(1, count($content['errors']));
        $this->assertContains('some error', $content['errors'][0]);
    }
}
