<?php

namespace MediaMonks\SonataMediaBundle\EventListener;

use Knp\Menu\ItemInterface;
use MediaMonks\SonataMediaBundle\Provider\ProviderPool;
use Sonata\AdminBundle\Event\ConfigureMenuEvent;
use Symfony\Component\Translation\TranslatorInterface;

class MenuBuilderListener
{
    /**
     * @var ProviderPool
     */
    private $providerPool;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $route;

    /**
     * @param ProviderPool $providerPool
     * @param TranslatorInterface $translator
     * @param string $route
     */
    public function __construct(
        ProviderPool $providerPool,
        TranslatorInterface $translator,
        $route = 'admin_mediamonks_sonatamedia_media_create'
    )
    {
        $this->providerPool = $providerPool;
        $this->translator = $translator;
        $this->route = $route;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function addMenuItems(ConfigureMenuEvent $event)
    {
        $menu = $event->getMenu();

        $child = $menu->getChild('Media');
        if (empty($child)) {
            return;
        }
        $child->setLabel($this->translator->trans('menu.title'));

        foreach ($this->providerPool->getProviders() as $providerClass => $provider) {
            $this->addProviderMenuChild(
                $child,
                $providerClass,
                $this->route,
                ['provider' => $providerClass],
                $provider->getName(),
                $provider->getIcon()
            );
        }
    }

    /**
     * @param ItemInterface $menu
     * @param string $name
     * @param string $route
     * @param array $routeParameters
     * @param string $label
     * @param string $icon
     */
    private function addProviderMenuChild(ItemInterface $menu, $name, $route, array $routeParameters, $label, $icon)
    {
        $child = $menu->addChild($name, ['route' => $route, 'routeParameters' => $routeParameters]);
        $child->setLabel($this->translator->trans('menu.provider', ['%provider%' => $this->translator->trans($label)]));
        $child->setAttribute('icon', sprintf('<i class="%s" aria-hidden="true"></i>', $icon));
    }
}
