<section id="main">
    <div class="page-header">
        <h2><?= t('BigBoard') ?> : <?= t('manage project selection') ?></h2>
    </div>
    <form method="post" action="<?= $this->url->href('Bigboard', 'saveList', ['plugin' => 'Bigboard']) ?>" autocomplete="off">
        <?= $this->form->csrf() ?>
        <div>
            <small>
                <span class="btn" id="selectAll"><?= t('check') ?> <b><?= t('everything') ?></b></span>
                <span class="btn" id="clearAll"><?= t('uncheck') ?> <b><?= t('everything') ?></b></span>
                <?php if (! empty($starred)): ?>
                    <span class="btn" id="addStar"><?= t('add') ?> <b><?= t('favorites') ?></b></span>
                    <span class="btn" id="onlyStar"><?= t('check') ?> <u><?= t('only') ?></u> <b><?= t('favorites') ?></b></span>
                <?php endif ?>
                <hr>
            </small>
        </div>

        <?php foreach ($projects as $project): ?>
            <?php $isStarred = in_array((int) $project['id'], $starred, true) ?>
            <div class="selitem" style="display: block;">
                <label class="sel">
                    <input type="checkbox" name="selection[]" class="<?= $isStarred ? 'fav' : '' ?>" value="<?= (int) $project['id'] ?>" <?= in_array((int) $project['id'], $selected, true) ? 'checked' : '' ?>>
                    <span>
                        <small>#<?= (int) $project['id'] ?></small>
                        <?php if ($isStarred): ?><i class="fa fa-star" title="<?= t('Favorite project') ?>"></i><?php endif ?>
                        <?= $this->text->e($project['name']) ?>
                        <?php if ($project['is_private']): ?><i class="fa fa-lock fa-fw" title="<?= t('Private project') ?>"></i><?php endif ?>
                    </span>
                </label>
            </div>
        <?php endforeach ?>

        <?= $this->modal->submitButtons() ?>
    </form>
</section>
