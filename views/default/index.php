<?php
/**
 * sjaakp/yii2-linkchecker
 * ----------
 * Link checker module for Yii2 framework
 * Version 1.0.0
 * Copyright (c) 2021
 * Sjaak Priester, Amsterdam
 * MIT License
 * https://github.com/sjaakp/yii2-linkchecker
 * https://sjaakpriester.nl
 */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\widgets\Menu;

/* @var $this yii\web\View */
/* @var $mode string */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $lastScan string */

$titles = [
    'error' => 'Error Links',
    'redirect' => 'Redirection Links',
    'success' => 'Success Links',
    'all' => 'All Links'
];

$this->title = Yii::t('linkchecker', $titles[$mode]);
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss('
.nav-link {
    font-weight: bold;
    color: white;
}
.nav-link:hover {
    color: white;
}
.active .nav-link {
    opacity: .6;
}
table {
    table-layout: fixed;
    width: 100%;
}
td {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
th:nth-child(1),
td:nth-child(1) {
    width: 3em;
}
th:nth-child(2),
td:nth-child(2) {
    width: 2em;
}
th:nth-child(4),
td:nth-child(4) {
    width: 9em;
}
th:nth-child(5),
td:nth-child(5) {
    width: 6em;
}
th:nth-child(6),
td:nth-child(6) {
    width: 15em;
}
.container {
    position: relative;
}
.spinner {
    width: 3em;
    height: 3em;
    position: absolute;
    right: 2em;
    animation: spin 2s infinite linear;
    display: none;
}
.spinner-show {
    display: block;
}
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}
');

$this->registerJs('
document.getElementById("scan_btn").addEventListener("click", e => {
    console.log(e);
    e.target.style.opacity = .4;
    document.getElementById("spn").classList.add("spinner-show");
});
');
?>
<div class="spinner" id="spn">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M296 48c0 22.091-17.909 40-40 40s-40-17.909-40-40 17.909-40 40-40 40 17.909 40 40zm-40 376c-22.091 0-40 17.909-40 40s17.909 40 40 40 40-17.909 40-40-17.909-40-40-40zm248-168c0-22.091-17.909-40-40-40s-40 17.909-40 40 17.909 40 40 40 40-17.909 40-40zm-416 0c0-22.091-17.909-40-40-40S8 233.909 8 256s17.909 40 40 40 40-17.909 40-40zm20.922-187.078c-22.091 0-40 17.909-40 40s17.909 40 40 40 40-17.909 40-40c0-22.092-17.909-40-40-40zm294.156 294.156c-22.091 0-40 17.909-40 40s17.909 40 40 40c22.092 0 40-17.909 40-40s-17.908-40-40-40zm-294.156 0c-22.091 0-40 17.909-40 40s17.909 40 40 40 40-17.909 40-40-17.909-40-40-40z"/></svg>
</div>

<h1><?= Html::encode($this->title) ?></h1>

<?= Menu::widget([
    'items' => [
        [
            'label' => Yii::t('linkchecker', 'Error Links'),
            'url' => ['default/index'],
            'options' => [ 'class' => 'nav-item ml-0' ],
        ],
        [
            'label' => Yii::t('linkchecker', 'Redirection Links'),
            'url' => ['default/redirection'],
        ],
        [
            'label' => Yii::t('linkchecker', 'Success Links'),
            'url' => ['default/success'],
        ],
        [
            'label' => Yii::t('linkchecker', 'All Links'),
            'url' => ['default/all'],
        ],
        [
            'label' => Yii::t('linkchecker', 'Scan'),
            'url' => ['default/scan'],
            'options' => [ 'class' => 'nav-item ml-auto' ],
            'template' => '<a id="scan_btn" class="nav-link bg-danger" href="{url}">{label}</a>'
        ],

    ],
    'options' => [ 'class' => 'nav nav-pills' ],
    'itemOptions' => [ 'class' => 'nav-item ml-1' ],
    'linkTemplate' => '<a class="nav-link bg-info" href="{url}">{label}</a>'
]) ?>

<p class="text-right text-info small">&nbsp;
<?php if ($lastScan): ?>
Last scan at: <?= Yii::$app->formatter->asDateTime($lastScan) ?></p>
<?php endif; ?>
</p>

<?php Pjax::begin(); ?>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        [
            'class' => 'yii\grid\SerialColumn',
            'header' => '',
            'contentOptions' => [ 'class' => 'text-secondary' ]
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{view}',
        ],
        [
            'attribute' => 'url',
            'content' => function($model, $key, $index, $column)    {
                return $column->grid->formatter->asUrl($model->url, [ 'target' => '_blank', 'data-pjax' => 0, 'title' => $model->url ]);
            },
        ],
        [
            'attribute' => 'source',
            'content' => function($model, $key, $index, $column)    {
                $src = $model->source;
                return Html::a($src, [ $src ], [ 'target' => '_blank', 'data-pjax' => 0, 'title' => $src ]);
            },
        ],
        'context:text',
        [
            'attribute' => 'status',
            'contentOptions' => function ($model, $key, $index, $column)    {
                $bcol = 'transparent';
                $tcol = 'reset';
                if ($model->status_code >= 300) {
                    $bcol = 'warning';
                    $tcol = 'light';
                }
                if (is_null($model->status_code) || $model->status_code >= 400) {
                    $bcol = 'danger';
                }
                return [ 'class' => "text-monospace font-monospace bg-$bcol text-$tcol" ];
            },
        ],
    ],
    'tableOptions' => ['class' => 'table table-sm table-bordered'],
    'summary' => Yii::t('linkchecker', '{begin}-{end}/{totalCount}'),
    'emptyText' => Yii::t('linkchecker', 'No Links found')
]); ?>
<?php Pjax::end(); ?>
