<?php

namespace Overdose\MagentoOptimizer\Test\Unit\Observer;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Overdose\MagentoOptimizer\Helper\Data;
use Overdose\MagentoOptimizer\Observer\Frontend\Http\ResponseSendBeforeOptimizeJS;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ResponseSendBeforeOptimizeJSTest extends TestCase
{
    /**
     * @var Data&MockObject|MockObject
     */
    private $dataMock;
    /**
     * @var SerializerInterface&MockObject|MockObject
     */
    private $serializerMock;
    /**
     * @var StoreManagerInterface&MockObject|MockObject
     */
    private $storeManagerMock;
    /**
     * @var ResponseSendBeforeOptimizeJS
     */
    private $observer;

    /**
     * Initialize test
     */
    public function setUp(): void
    {
        $this->dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new ResponseSendBeforeOptimizeJS(
            $this->dataMock,
            $this->serializerMock,
            $this->storeManagerMock
        );
    }

    /**
     * @dataProvider removeCommentDataProvider
     */
    public function testRemoveCommentContainingScript($html, $expected)
    {
        $reflection = new ReflectionClass(ResponseSendBeforeOptimizeJS::class);
        $method = $reflection->getMethod('removeCommentContainingScript');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->observer, [$html]);

        $this->assertSame($expected, $result);
    }

    public function removeCommentDataProvider()
    {
        return [
            'commented_script_only' => [
                'html' => '<!-- <script> lorem ipsum dolor </script> -->',
                'expected' => '<!---->'
            ],
            'uncommented_script_tag_only' => [
                'html' => '<script> lorem ipsum dolor </script>',
                'expected' => '<script> lorem ipsum dolor </script>'
            ],
            'commented_and_uncommented' => [
                'html' => '<script> lorem ipsum dolor </script><!-- <script> sit amet </script> -->',
                'expected' => '<script> lorem ipsum dolor </script><!---->'
            ],
            'commented_new_line' => [
                'html' => '<script> lorem ipsum dolor </script><!-- <script> sit amet
 </script> -->',
                'expected' => '<script> lorem ipsum dolor </script><!---->'
            ],
            'double_commented_and_uncommented' => [
                'html' => '<script> lorem ipsum dolor </script><!-- <script> sit amet </script> lorem ipsum <script> dolor
sit amet </script> amet --><script> lorem </script>',
                'expected' => '<script> lorem ipsum dolor </script><!----><script> lorem </script>'
            ],
        ];
    }
}
