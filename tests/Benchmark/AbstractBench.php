<?php

declare(strict_types=1);

/*
 * This file is part of the WebPush library.
 *
 * (c) Louis Lagrange <lagrange.louis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Minishlink\Tests\Benchmark;

use Http\Mock\Client;
use Minishlink\WebPush\ExtensionManager;
use Minishlink\WebPush\Notification;
use Minishlink\WebPush\Payload\AES128GCM;
use Minishlink\WebPush\Payload\AESGCM;
use Minishlink\WebPush\Payload\PayloadExtension;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\TopicExtension;
use Minishlink\WebPush\TTLExtension;
use Minishlink\WebPush\UrgencyExtension;
use Minishlink\WebPush\VAPID\JWSProvider;
use Minishlink\WebPush\VAPID\VAPID;
use Minishlink\WebPush\WebPush;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * @BeforeMethods({"init"})
 * @Revs(4096)
 */
abstract class AbstractBench
{
    private WebPush $webPush;
    private WebPush $webPushWithCache;
    private Subscription $subscription;

    public function init(): void
    {
        $client = new Client();
        $psr17Factory = new Psr17Factory();

        $jwsProvider = $this->jwtProvider();
        $vapidExtension = new VAPID('mailto:foo@bar.com', $jwsProvider);
        $vapidExtensionWithCache = new VAPID('mailto:foo@bar.com', $jwsProvider);
        $vapidExtensionWithCache
            ->setCache(new FilesystemAdapter())
        ;

        $payloadExtension = new PayloadExtension();
        $payloadExtension
            ->addContentEncoding(new AES128GCM())
            ->addContentEncoding(new AESGCM())
        ;

        $aes128gcm = new AES128GCM();
        $aes128gcm->setCache(new FilesystemAdapter());
        $aesgcm = new AESGCM();
        $aesgcm->setCache(new FilesystemAdapter());

        $payloadExtensionWithCache = new PayloadExtension();
        $payloadExtensionWithCache
            ->addContentEncoding($aes128gcm)
            ->addContentEncoding($aesgcm)
        ;

        $extensionManager = new ExtensionManager();
        $extensionManager
            ->add(new TTLExtension())
            ->add(new TopicExtension())
            ->add(new UrgencyExtension())
            ->add($vapidExtension)
            ->add($payloadExtension)
        ;

        $extensionManagerWithCache = new ExtensionManager();
        $extensionManagerWithCache
            ->add(new TTLExtension())
            ->add(new TopicExtension())
            ->add(new UrgencyExtension())
            ->add($vapidExtensionWithCache)
            ->add($payloadExtensionWithCache)
        ;

        $this->webPush = new WebPush($client, $psr17Factory, $extensionManager);
        $this->webPushWithCache = new WebPush($client, $psr17Factory, $extensionManagerWithCache);

        $this->subscription = Subscription::createFromString('{"endpoint":"https://updates.push.services.mozilla.com/wpush/v2/gAAAAABfcsdu1p9BdbYIByt9F76MHcSiuix-ZIiICzAkU9z_p0gnolYLMOi71rqss5pMOZuYJVZLa7rRN58uOgfdsux7k51Ph6KJRFEkf1LqTRMv2d8OhQaL2TR36WUR2d5twzYVwcQJAnTLrhVrWqKVo8ekAonuwyFHDUGzD8oUWpFTK9y2F68","keys":{"auth":"wSfP1pfACMwFesCEfJx4-w","p256dh":"BIlDpD05YLrVPXfANOKOCNSlTvjpb5vdFo-1e0jNcbGlFrP49LyOjYyIIAZIVCDAHEcX-135b859bdsse-PgosU"},"contentEncoding":"aes128gcm"}');
    }

    /**
     * @Subject
     */
    public function sendNotificationWithoutPayload(): void
    {
        $notification = Notification::create();
        $this->webPush->send($notification, $this->subscription);
    }

    /**
     * @Subject
     */
    public function sendNotificationWithoutPayloadWithCache(): void
    {
        $notification = Notification::create();
        $this->webPushWithCache->send($notification, $this->subscription);
    }

    /**
     * @Subject
     */
    public function sendNotificationWithPayload(): void
    {
        $notification = Notification::create()
            ->withPayload('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas nisi justo, cursus sed fringilla at, mollis ac velit. Duis vulputate libero eget luctus posuere. Nam in ex turpis. Nullam commodo elit tortor. Phasellus ipsum sapien, venenatis non tellus et, ullamcorper faucibus felis. Nullam quis eleifend diam, ut tincidunt nibh. Ut massa lectus, imperdiet a mollis sed, tempor a arcu. Nulla facilisi.')
        ;
        $this->webPush->send($notification, $this->subscription);
    }

    /**
     * @Subject
     */
    public function sendNotificationWithPayloadWithCache(): void
    {
        $notification = Notification::create()
            ->withPayload('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas nisi justo, cursus sed fringilla at, mollis ac velit. Duis vulputate libero eget luctus posuere. Nam in ex turpis. Nullam commodo elit tortor. Phasellus ipsum sapien, venenatis non tellus et, ullamcorper faucibus felis. Nullam quis eleifend diam, ut tincidunt nibh. Ut massa lectus, imperdiet a mollis sed, tempor a arcu. Nulla facilisi.')
        ;
        $this->webPushWithCache->send($notification, $this->subscription);
    }

    abstract protected function jwtProvider(): JWSProvider;
}
