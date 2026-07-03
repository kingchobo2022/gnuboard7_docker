<?php

namespace Tests\Unit\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use App\Listeners\ExtensionConfigCacheListener;
use Tests\TestCase;

/**
 * ExtensionConfigCacheListener 회귀 테스트.
 *
 * 확장 활성화/비활성화 직후 config 캐시를 재생성하는 리스너.
 * install/uninstall/update 는 ExtensionManager::updateComposerAutoload() 가 담당하므로,
 * 이 리스너는 activate/deactivate 만 구독해야 한다(이중 재생성 회피). 구독 훅 집합이
 * 바뀌면 config 캐시가 재생성되지 않거나(누락) 두 번 재생성되는(낭비) 회귀가 발생한다.
 */
class ExtensionConfigCacheListenerTest extends TestCase
{
    public function test_implements_hook_listener_interface(): void
    {
        $this->assertInstanceOf(
            HookListenerInterface::class,
            new ExtensionConfigCacheListener
        );
    }

    public function test_subscribes_only_activate_and_deactivate_hooks(): void
    {
        $hooks = ExtensionConfigCacheListener::getSubscribedHooks();

        $expected = [
            'core.modules.activated',
            'core.modules.after_deactivate',
            'core.plugins.activated',
            'core.plugins.after_deactivate',
            'core.templates.activated',
            'core.templates.after_deactivate',
        ];

        $this->assertEqualsCanonicalizing($expected, array_keys($hooks));
    }

    public function test_does_not_subscribe_install_uninstall_update_hooks(): void
    {
        // install/uninstall/update 는 updateComposerAutoload 가 config 캐시를 재생성하므로
        // 이 리스너가 구독하면 이중 재생성이 된다. 구독 목록에서 제외되어야 한다.
        $hookNames = array_keys(ExtensionConfigCacheListener::getSubscribedHooks());

        foreach (['installed', 'updated'] as $lifecycle) {
            foreach (['modules', 'plugins', 'templates'] as $type) {
                $this->assertNotContains(
                    "core.{$type}.{$lifecycle}",
                    $hookNames,
                    "core.{$type}.{$lifecycle} 는 updateComposerAutoload 가 담당하므로 이 리스너가 구독하면 안 됩니다."
                );
            }
        }
    }

    public function test_all_hooks_route_to_on_extension_toggled(): void
    {
        $hooks = ExtensionConfigCacheListener::getSubscribedHooks();

        foreach ($hooks as $hookName => $config) {
            $this->assertSame('onExtensionToggled', $config['method'], "{$hookName} 은 onExtensionToggled 로 라우팅되어야 합니다.");
            $this->assertArrayHasKey('priority', $config);
        }
    }
}
