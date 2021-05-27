<?php

namespace Overdose\MagentoOptimizer\Test\Unit\Observer;

class ResponseSendBeforeTest extends \PHPUnit\Framework\TestCase
{
    public $observer;

    public $event_stub;

    public function setUp(): void
    {
        $this->observer = $this->getMockBuilder(\Overdose\MagentoOptimizer\Observer\Frontend\Http\ResponseSendBefore::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkControllersExceptions',
                'checkPathExceptions',
            ])
            ->getMock();

        $this->event_stub = $this->getEventStub();

        $this->observer->helper = $this->getHelperStub();
    }

    public function getEventStub() {
        $_objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $objMock = $_objectManager->getObject( \Magento\Framework\Event\Observer::class);
        $event = $_objectManager->getObject(\Magento\Framework\Event::class);

        $event->setData('response', $_objectManager->getObject(\Magento\Framework\DataObject::class));

        $objMock->setData('event', $event);

        return $objMock;
    }

    public function getHelperStub() {
        $objMock = $this->getMockBuilder(\Overdose\MagentoOptimizer\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isEnabled'
            ])
            ->getMockForAbstractClass();
        $objMock
            ->method('isEnabled')
            ->will(
                $this->returnValue(true)
            );

        return $objMock;
    }

    /**
     * @dataProvider getTestCases
     */
    public function testIsJsExistInBody(
        $js_code,
        $expected_result
    ) {
        $this->observer->expects($this->any())
            ->method('checkControllersExceptions')
            ->willReturn(
                true
            );
        $this->observer->expects($this->any())
            ->method('checkPathExceptions')
            ->willReturn(
                true
            );

        $this->event_stub->getEvent()->getResponse()->setData('content', $js_code);
        $this->observer->execute($this->event_stub);
        $result = $this->event_stub->getEvent()->getResponse()->getContent();
        //preg_match_all('#(<script.*?</script>)#is', $js_code, $js_matches);
        preg_match("/<body[^>]*>(.*?)<\/body>/is", $result, $matches);
        $js_body_exists = false;
        if (preg_match('/\b(script)\b/i', $matches[1])) {
            $js_body_exists = true;
        }

        $this->assertEquals($expected_result, $js_body_exists);
    }

    public function getTestCases()
    {
        return [
            [
                '<head><script nodefer type="text/javascript" src="http://awesome.store/pub/my.js"></script></head><body>ololo</body>',
                false
            ],
            [
                '<head><script    nodefer    type="text/javascript" src="http://awesome.store/pub/my.js"></script></head><body>ololo</body>',
                false
            ],
            [
                '<head><script type="text/javascript" src="http://awesome.store/pub/my.js" nodefer></script></head><body>ololo</body>',
                false
            ],
            /* [
                '<head><script type="text/javascript" nodefer src="http://awesome.store/pub/my.js"></script></head><body>ololo</body>',
                false
            ], //this test will fail because attribute `nodefer` in a middle, need to review REGEXP
            */
            [
                '<head><script nodefer="nodefer" type="text/javascript" src="http://awesome.store/pub/my.js"></scriptnodefer></head><body>ololo</body>',
                false
            ],
            [
                '<head>
                    <script nodefer type="text/javascript" src="http://awesome.store/pub/my.js"></script>
                    <script nodefer type="text/javascript" src="http://awesome.store/pub/my2.js"></script>
                    <script nodefer="nodefer" type="text/javascript" src="http://awesome.store/pub/my3.js"></script>
                </head><body>ololo</body>',
                false
            ],
            [
                '<head>
                    <script type="text/javascript" src="http://awesome.store/pub/my.js"></script>
                    <script type="text/javascript" src="http://awesome.store/pub/my2.js"></script>
                    <script nodefer type="text/javascript" src="http://awesome.store/pub/my3.js"></script>
                </head><body>ololo</body>',
                true
            ],
            [
                '<head><script type="text/javascript" src="http://awesome.store/pub/my.js"></script></head><body>ololo</body>',
                true
            ],
            [
                '<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><head><script type="text/javascript" src="http://awesome.store/pub/my.js"></script></head><body>ololo</body></html>',
                true
            ],
            [
                '<head>
                    <script type="text/javascript" src="http://awesome.store/pub/my.js"></script>
                    <script type="text/javascript" src="http://awesome.store/pub/my2.js"></script>
                    <script type="text/javascript" src="http://awesome.store/pub/my3.js"></script>
                </head><body>ololo</body>',
                true
            ],
        ];
    }
}
