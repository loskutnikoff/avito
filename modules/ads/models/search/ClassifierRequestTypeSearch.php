<?php

namespace app\modules\ads\models\search;

use app\modules\ads\models\ClassifierRequestType;
use yii\data\ActiveDataProvider;

class ClassifierRequestTypeSearch extends ClassifierRequestType
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'platform_type', 'request_type_id', 'source_id'], 'integer'],
        ];
    }

    public function search($params): ActiveDataProvider
    {
        $query = ClassifierRequestType::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'platform_type' => $this->platform_type,
            'request_type_id' => $this->request_type_id,
            'source_id' => $this->source_id,
        ]);

        return $dataProvider;
    }
}
