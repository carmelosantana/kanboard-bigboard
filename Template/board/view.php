<section id="main">
    <div class="header btnbox">
        <?php if ($this->user->hasProjectAccess('ProjectViewController', 'show', $project['id'])): ?>
            <?= $this->url->icon('cog', '', 'ProjectViewController', 'show', ['project_id' => $project['id']], false, 'btn') ?>
            <?= $this->modal->mediumButton('plus', '', 'SwimlaneController', 'create', ['project_id' => $project['id']]) ?>
        <?php endif ?>
        <div class="btn bbprojectcollapse" data-url="<?= $this->url->href('BoardAjaxController', 'collapseProject', ['plugin' => 'Bigboard', 'project_id' => $project['id']]) ?>">
            <span class="collapsible" data-project-id="<?= (int) $project['id'] ?>" id="head-<?= (int) $project['id'] ?>">
                <i id="open-<?= (int) $project['id'] ?>" style="display:<?= $is_collapsed ? 'none' : 'inline-block' ?>" class="fa fa-folder-open open"></i>
                <i id="close-<?= (int) $project['id'] ?>" style="display:<?= $is_collapsed ? 'inline-block' : 'none' ?>" class="fa fa-folder close"></i>
                <i id="wip-<?= (int) $project['id'] ?>" style="display:none" class="fa fa-cog fa-spin fa-fw"></i>
            </span>
            <small>#<?= (int) $project['id'] ?></small>
            <?php if ($is_starred): ?><i class="fa fa-star" title="<?= t('Favorite project') ?>"></i><?php endif ?>
            <?= $this->text->e($project['name']) ?>
            <?php if ($project['is_private']): ?><i class="fa fa-lock fa-fw" title="<?= t('Private project') ?>"></i><?php endif ?>
            <?php if (! empty($project['description'])): ?>
                <?= $this->app->tooltipMarkdown($project['description']) ?>
            <?php endif ?>
        </div>
    </div>

    <div class="BBcontent" data-project-id="<?= (int) $project['id'] ?>" style="display:<?= $is_collapsed ? 'none' : 'block' ?>">
        <?= $this->render('bigboard:board/table_container', [
            'project' => $project,
            'swimlanes' => $swimlanes,
            'board_private_refresh_interval' => $board_private_refresh_interval,
            'board_highlight_period' => $board_highlight_period,
        ]) ?>
    </div>
</section>
