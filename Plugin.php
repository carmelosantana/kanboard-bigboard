<?php

namespace Kanboard\Plugin\Bigboard;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;
use Kanboard\Plugin\Bigboard\Model\BigboardModel;
use Kanboard\Plugin\Bigboard\Model\MoveGuard;

class Plugin extends Base
{
    public function initialize()
    {
        $this->container['bigboardModel'] = function ($c) {
            return new BigboardModel($c);
        };
        $this->container['bigboardMoveGuard'] = function ($c) {
            return new MoveGuard($c);
        };

        $this->template->hook->attach('template:project-list:menu:before', 'bigboard:Bigboard');
        $this->template->hook->attach('template:header:dropdown', 'bigboard:header/user_dropdown');
        $this->template->hook->attach('template:dashboard:page-header:menu', 'bigboard:header/bigboard_header_link');
        $this->template->hook->attach('template:project-header:view-switcher', 'bigboard:header/bigboard_header_link');

        if ($this->request->getStringParam('plugin') === 'Bigboard') {
            $this->template->setTemplateOverride('board/table_container', 'bigboard:board/table_container');
            $this->template->setTemplateOverride('board/table_tasks', 'bigboard:board/table_tasks');

            foreach (['BoardDragAndDrop', 'BoardPolling', 'bigboard-selected', 'bigboard-collapsed'] as $script) {
                $this->hook->on('template:layout:js', ['template' => 'plugins/Bigboard/Asset/js/'.$script.'.js']);
            }
            $this->hook->on('template:layout:css', ['template' => 'plugins/Bigboard/Asset/css/bigboard.css']);
        }
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getPluginName()
    {
        return 'Bigboard';
    }

    public function getPluginDescription()
    {
        return t('BigBoard: one board showing several projects at once, filtered to the projects you can access.');
    }

    public function getPluginAuthor()
    {
        return 'Carmelo Santana (fork of BlueTeck, Thomas Stinner, Pierre Cadeot, PapeCoding, Chaosmeister, Jake G)';
    }

    public function getPluginVersion()
    {
        return '2.0.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/carmelosantana/kanboard-bigboard';
    }

    public function getPluginLicense()
    {
        return 'MIT';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.47';
    }
}
