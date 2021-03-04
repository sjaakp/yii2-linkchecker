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

namespace sjaakp\linkchecker\controllers;

use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use sjaakp\linkchecker\models\Link;

/**
 * Default controller for the `linkchecker` module
 */
class DefaultController extends Controller
{
    /**
     * Lists all Error Links.
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->report(['or', ['status_code' => null], ['>=', 'status_code', 400]], 'error');
    }
    /**
     * Lists all Redirection Links.
     * @return mixed
     */
    public function actionRedirection()
    {
        return $this->report(['between', 'status_code', 300, 399], 'redirect');
    }
    /**
     * Lists all Success Links.
     * @return mixed
     */
    public function actionSuccess()
    {
        return $this->report(['between', 'status_code', 200, 299], 'success');
    }
    /**
     * Lists all Links.
     * @return mixed
     */
    public function actionAll()
    {
        return $this->report(null, 'all');
    }

    /**
     * Displays details of a single Link.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Collects and checks links
     * @return string
     */
    public function actionScan()
    {
        $t = time();
        Link::clearLinks();

        foreach ($this->module->source as $src) {
            if (! isset($src['model'])) {
                throw new InvalidConfigException('Linkchecker: source model is missing.');
            }
            $model = $src['model'];
            $where = $src['where'] ?? [];
            $urlAtts = $src['urlAttributes'] ?? [];
            $htmlAtts = $src['htmlAttributes'] ?? [];
            $mode = $src['mode'] ?? 'abs';
            Link::getLinks($model, $urlAtts, $where, $mode);
            Link::collectLinks($model, $htmlAtts, $where, $mode);
        }
        Link::scanLinks();
        Yii::$app->session->setFlash('success', Yii::t('linkchecker', '{n} links scanned, {t} seconds', [
            'n' => Link::find()->count(),
            't' => time() - $t
        ]));
        return $this->redirect(['index']);
    }

    /**
     * @param $where string|array
     * @param $mode string
     * @return string
     */
    protected function report($where, $mode)
    {
        $query = Link::find()->where($where)->orderBy('url');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => false
        ]);

        return $this->render('index', [
            'mode' => $mode,
            'dataProvider' => $dataProvider,
            'lastScan' => $query->max('date')
        ]);
    }

    /**
     * Finds the Link model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Link the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Link::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('linkchecker', 'The requested Link does not exist.'));
    }
}
