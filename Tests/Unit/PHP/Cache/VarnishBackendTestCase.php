<?php

namespace DMK\Mkvarnish\Tests\Unit\Hooks;

use DMK\Mkvarnish\Cache\VarnishBackend;
use DMK\Mkvarnish\Repository\CacheTagsRepository;
use DMK\Mkvarnish\Tests\Unit\MkVarnishBaseTest;
use DMK\Mkvarnish\Utility\Configuration;
use DMK\Mkvarnish\Utility\CurlQueue;

/***************************************************************
 * Copyright notice
 *
 * (c) 2017 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * DMK\Mkvarnish\Tests\Unit\Hooks$VarnishBackendTest.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class VarnishBackendTestCase extends MkVarnishBaseTest
{
    /**
     * @var string
     */
    private $siteNameBackup;

    /**
     * @var array
     */
    private $extConfBackup = [];

    /**
     * {@inheritdoc}
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->siteNameBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $this->extConfBackup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get('mkvarnish');
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = $this->siteNameBackup;
        $this->setExtensionConfiguration($this->extConfBackup);
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testThrowExceptionIfNotImplemented()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('the varnish cache backend can only remove cache entries by tags or the complete cache at the moment');
        $this->callInaccessibleMethod($this->getVarnishBackendInstance(), 'throwExceptionIfNotImplemented');
    }

    /**
     * @dataProvider dataProviderUnimplementedMethods
     */
    public function testUnimplementedMethods($method, array $arguments)
    {
        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->setMethods(['throwExceptionIfNotImplemented'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('throwExceptionIfNotImplemented');

        call_user_func_array([$varnishBackend, $method], $arguments);
    }

    /**
     * @return string[][]|string[][][]
     */
    public function dataProviderUnimplementedMethods()
    {
        return [
            'method set, line: '.__LINE__ => ['set', ['test', []]],
            'method get, line: '.__LINE__ => ['get', ['test']],
            'method remove, line: '.__LINE__ => ['remove', ['test']],
            'method has, line: '.__LINE__ => ['has', ['test']],
            'method findIdentifiersByTag, line: '.__LINE__ => ['findIdentifiersByTag', ['test']],
        ];
    }

    /**
     * @return void
     */
    public function testGetHmacForSitename()
    {
        $varnishBackend = $this->getVarnishBackendInstance();
        $firstHmac = $this->callInaccessibleMethod($varnishBackend, 'getHmacForSitename');
        $secondHmac = $this->callInaccessibleMethod($varnishBackend, 'getHmacForSitename');

        self::assertSame($firstHmac, $secondHmac, 'hmac for sitename is not same in 2 calls');
        self::assertIsString($firstHmac, 'hmac is no string');
        self::assertGreaterThan(30, strlen($firstHmac), 'hmac is not at least 30 chars long');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'test site mkvarnish';
        $hmacAfterSiteNameChanged = $this->callInaccessibleMethod($varnishBackend, 'getHmacForSitename');
        self::assertNotSame($firstHmac, $hmacAfterSiteNameChanged, 'hmac for different site names is not different');
    }

    /**
     * @return void
     */
    public function testConvertCacheTagForPurge()
    {
        $convertedCacheTagForPurge = $this->callInaccessibleMethod(
            $this->getVarnishBackendInstance(),
            'convertCacheTagForPurge',
            'tt_content_5'
        );

        self::assertEquals('(tt_content_5)(,.+)?$', $convertedCacheTagForPurge);
    }

    /**
     * @return void
     */
    public function testGetHostNamesForPurge()
    {
        $this->setExtensionConfiguration([]);
        $varnishBackend = $this->getVarnishBackendInstance();

        $httpHost = '';
        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $httpHost = (string) $_SERVER['HTTP_HOST'];
        }

        self::assertContains(
            $httpHost,
            $this->callInaccessibleMethod($varnishBackend, 'getHostNamesForPurge')
        );
    }

    /**
     * @return void
     */
    public function testGetCurlQueueUtility()
    {
        self::assertInstanceOf(
            CurlQueue::class,
            $this->callInaccessibleMethod($this->getVarnishBackendInstance(), 'getCurlQueueUtility')
        );
    }

    /**
     * @return void
     */
    public function testExecutePurge()
    {
        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->setMethods(['getHmacForSitename', 'getCurlQueueUtility', 'getHostNamesForPurge'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('getHmacForSitename')
            ->will($this->returnValue('abc123'));

        $varnishBackend
            ->expects(self::once())
            ->method('getHostNamesForPurge')
            ->will($this->returnValue(['firstHost', 'secondHost']));

        $curlQueueUtility = $this->getMockBuilder(CurlQueue::class)
            ->setMethods(['addCommand'])
            ->getMock();
        $curlQueueUtility
            ->expects(self::exactly(2))
            ->method('addCommand')
            ->withConsecutive(
                [
                    'PURGE',
                    'firstHost',
                    ['X-Varnish-Purge-All: 1', 'X-TYPO3-Sitename: abc123'],
                ],
                [
                    'PURGE',
                    'secondHost',
                    ['X-Varnish-Purge-All: 1', 'X-TYPO3-Sitename: abc123'],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnValue($curlQueueUtility),
                $this->returnValue($curlQueueUtility)
            );

        $varnishBackend
            ->expects(self::once())
            ->method('getCurlQueueUtility')
            ->will($this->returnValue($curlQueueUtility));

        $this->callInaccessibleMethod($varnishBackend, 'executePurge', ['X-Varnish-Purge-All' => 1]);
    }

    /**
     * @return void
     */
    public function testFlush()
    {
        $configurationUtility = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configurationUtility
            ->expects(self::once())
            ->method('isSendCacheHeadersEnabled')
            ->will(self::returnValue(true));

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->setMethods(['executePurge', 'truncateCacheTagsTable', 'getConfigurationUtility'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('getConfigurationUtility')
            ->will(self::returnValue($configurationUtility));

        $varnishBackend
            ->expects(self::once())
            ->method('executePurge')
            ->with(['X-Varnish-Purge-All' => 1]);

        $varnishBackend
            ->expects(self::once())
            ->method('truncateCacheTagsTable');

        $varnishBackend->flush();
    }

    /**
     * @return void
     */
    public function testFlushWhenNotSendCacheHeaderEnabled()
    {
        $configurationUtility = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configurationUtility
            ->expects(self::once())
            ->method('isSendCacheHeadersEnabled')
            ->will(self::returnValue(false));

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->setMethods(['executePurge', 'truncateCacheTagsTable', 'getConfigurationUtility'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('getConfigurationUtility')
            ->will(self::returnValue($configurationUtility));

        $varnishBackend
            ->expects(self::never())
            ->method('executePurge');

        $varnishBackend
            ->expects(self::never())
            ->method('truncateCacheTagsTable');

        $varnishBackend->flush();
    }

    /**
     * @return void
     */
    public function testFlushByTag()
    {
        $configurationUtility = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configurationUtility
            ->expects(self::once())
            ->method('isSendCacheHeadersEnabled')
            ->will(self::returnValue(true));

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->setMethods(['executePurge', 'convertCacheTagForPurge', 'deleteFromCacheTagsTableByTag', 'getConfigurationUtility'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('getConfigurationUtility')
            ->will(self::returnValue($configurationUtility));

        $varnishBackend
            ->expects(self::once())
            ->method('convertCacheTagForPurge')
            ->with('testTag')
            ->will($this->returnValue('convertedTag'));

        $varnishBackend
            ->expects(self::once())
            ->method('executePurge')
            ->with(['X-Cache-Tags' => 'convertedTag']);

        $varnishBackend
            ->expects(self::once())
            ->method('deleteFromCacheTagsTableByTag')
            ->with('testTag');

        $varnishBackend->flushByTag('testTag');
    }

    /**
     * @return void
     */
    public function testFlushByTagWhenNotSendCacheHeaderEnabled()
    {
        $configurationUtility = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isSendCacheHeadersEnabled'])
            ->getMock();
        $configurationUtility
            ->expects(self::once())
            ->method('isSendCacheHeadersEnabled')
            ->will(self::returnValue(false));

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->setMethods(['executePurge', 'convertCacheTagForPurge', 'deleteFromCacheTagsTableByTag', 'getConfigurationUtility'])
            ->disableOriginalConstructor()
            ->getMock();

        $varnishBackend
            ->expects(self::once())
            ->method('getConfigurationUtility')
            ->will(self::returnValue($configurationUtility));

        $varnishBackend
            ->expects(self::never())
            ->method('convertCacheTagForPurge');

        $varnishBackend
            ->expects(self::never())
            ->method('executePurge');

        $varnishBackend
            ->expects(self::never())
            ->method('deleteFromCacheTagsTableByTag');

        $varnishBackend->flushByTag('testTag');
    }

    /**
     * @group unit
     */
    public function testGetCacheTagsRepository()
    {
        self::assertInstanceOf(
            CacheTagsRepository::class,
            $this->callInaccessibleMethod($this->getVarnishBackendInstance(), 'getCacheTagsRepository')
        );
    }

    /**
     * @return void
     * @test
     */
    public function testTruncateCacheTagsTable()
    {
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['truncateTable'])
            ->getMock();

        $cacheTagsRepository
            ->expects(self::once())
            ->method('truncateTable');

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->setMethods(['getCacheTagsRepository'])
            ->disableOriginalConstructor()
            ->getMock();
        $varnishBackend
            ->expects(self::once())
            ->method('getCacheTagsRepository')
            ->will($this->returnValue($cacheTagsRepository));

        $this->callInaccessibleMethod($varnishBackend, 'truncateCacheTagsTable');
    }

    /**
     * @return void
     * @test
     */
    public function testDeleteFromCacheTagsTableByTag()
    {
        $cacheTagsRepository = $this->getMockBuilder(CacheTagsRepository::class)
            ->setMethods(['deleteByTag'])
            ->getMock();

        $cacheTagsRepository
            ->expects(self::once())
            ->method('deleteByTag')
            ->with('test_tag');

        $varnishBackend = $this->getMockBuilder(VarnishBackend::class)
            ->setMethods(['getCacheTagsRepository'])
            ->disableOriginalConstructor()
            ->getMock();
        $varnishBackend
            ->expects(self::once())
            ->method('getCacheTagsRepository')
            ->will($this->returnValue($cacheTagsRepository));

        $this->callInaccessibleMethod($varnishBackend, 'deleteFromCacheTagsTableByTag', 'test_tag');
    }

    /**
     * @group unit
     */
    public function testGetConfigurationUtility()
    {
        self::assertInstanceOf(
            Configuration::class,
            $this->callInaccessibleMethod($this->getVarnishBackendInstance(), 'getConfigurationUtility')
        );
    }

    /**
     * @return \DMK\Mkvarnish\Cache\VarnishBackend
     */
    private function getVarnishBackendInstance()
    {
        return new VarnishBackend('Testing');
    }
}
