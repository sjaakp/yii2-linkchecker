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
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model sjaakp\linkchecker\models\Link */

$this->title = Yii::t('linkchecker', 'Link Details');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Links'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$url = $model->url;
$loc = $model->location;

for ($i = strlen($url) - 1, $j = strlen($loc) - 1; $i >= 0 && $j >= 0; $i--, $j--) {
    if ($url[$i] != $loc[$j]) break;
}

if ($j >= 0) $loc = substr_replace($loc, '</ins>', $j + 1, 0);

for ($i = 0, $j = 0; $i < strlen($url) && $j < strlen($loc); $i++, $j++) {
    if ($url[$i] != $loc[$j]) break;
}

if ($j >= 0) $loc = substr_replace($loc, '<ins>', $j, 0);

$this->registerCss('
ins {
    font-weight: bold;
    color: black;
    text-decoration: none;
}
')
?>
<h1><?= Html::encode($this->title) ?></h1>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        [
            'attribute' => 'url',
            'value' => function($model, $widget)    {
                return $widget->formatter->asUrl($model->url, [ 'target' => '_blank' ]);
            },
            'format' => 'raw',
        ],
        [
            'attribute' => 'source',
            'value' => function($model, $widget)    {
                $src = $model->source;
                return Html::a($src, [ $src ], [ 'target' => '_blank' ]);
            },
            'format' => 'raw',
        ],
        'context:text',
        'src_model:text',
        'src_field:ntext',
        'src_pos',
        'status:text',
        'status_code',
        [
            'attribute' => 'location',
            'value' => function($model, $widget) use ($loc)   {
                return Html::a($loc, [ $model->location ], [ 'target' => '_blank' ]);
            },
            'format' => 'raw',
        ],
        'time:decimal',
        'date:datetime',
        'headers:ntext'
    ],
    'options' => ['class' => 'table table-sm table-bordered'],
    'template' => '<tr><th{captionOptions}>{label}</th><td class="text-monospace font-monospace">{value}</td></tr>'
]) ?>
