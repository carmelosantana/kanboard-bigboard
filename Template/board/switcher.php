<div class="table-list">
	<div class="views-switcher-component">
		<ul class="views">
			<li>
				<?= $this->url->icon('home', t('Home'), 'DashboardController', 'show') ?>
			</li>
			<li>
				<?= $this->url->icon('eye', t('Projects Selection'), 'Bigboard', 'select', ['plugin' => 'Bigboard', 'boardview' => 'active', ], false, 'js-modal-medium') ?>
			</li>
			<li class="collapse_all" data-url="<?= $this->url->href('BoardAjaxController', 'collapseAllProjects', ['plugin' => 'Bigboard']) ?>">
				<a href="#"><i class="fa fa-folder-o fa-fw"></i><?= t('Collapse projects') ?></a>
			</li>
			<li class="expand_all" data-url="<?= $this->url->href('BoardAjaxController', 'expandAllProjects', ['plugin' => 'Bigboard']) ?>">
				<a href="#"><i class="fa fa-folder-open-o fa-fw"></i><?= t('Expand projects') ?></a>
			</li>
			<li>
				<span class="filter-display-mode" <?= $bigboarddisplaymode ? '' : 'style="display: none;"' ?>>
					<?= $this->url->icon('expand', t('Expand tasks'), 'Bigboard', 'expandAll', array('plugin' => 'Bigboard'), false, 'board-display-mode') ?>
				</span>
				<span class="filter-display-mode" <?= $bigboarddisplaymode ? 'style="display: none;"' : '' ?>>
					<?= $this->url->icon('compress', t('Collapse tasks'), 'Bigboard', 'collapseAll', array('plugin' => 'Bigboard'), false, 'board-display-mode') ?>
				</span>
			</li>
			<li>
				<span class="filter-compact">
					<a href="#" class="filter-toggle-scrolling" title="<?= t('Keyboard shortcut: "%s"', 'c') ?>">
						<i class="fa fa-th fa-fw"></i><?= t('Compact view') ?>
					</a>
				</span>
				<span class="filter-wide" style="display: none">
					<a href="#" class="filter-toggle-scrolling" title="<?= t('Keyboard shortcut: "%s"', 'c') ?>">
						<i class="fa fa-arrows-h fa-fw"></i><?= t('Horizontal scrolling') ?>
					</a>
				</span>
			</li>
			<li>
				<?= $this->url->icon('dashboard', t('Activity'), 'ActivityController', 'user', [], false, 'js-modal-medium') ?>
			</li>
		</ul>
	</div>
</div>
