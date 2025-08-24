<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Task;

/**
 * TaskSearch represents the model behind the search form of `app\models\Task`.
 */
class TaskSearch extends Task
{
    public $due_date_from;
    public $due_date_to;
    public $keyword;
    public $tag;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'priority', 'tag','keyword','due_date_from','due_date_to'], 'safe'],
            [['due_date_from','due_date_to'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param string|null $formName Form name to be used into `->load()` method.
     *
     * @return ActiveDataProvider
     */
    public function search($params, $formName = null)
    {
        $query = Task::find()->alias('t')->joinWith('tags')->distinct();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => isset($params['per_page']) && !empty($params['per_page']) ? $params['per_page'] : 10,
                'page' => isset($params['page']) && !empty($params['page']) ? $params['page'] - 1 : 0,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes' => ['created_at', 'due_date', 'priority'],
            ],
        ]);

        // Try to load with form name first (for web/GridView)
        if (!$this->load($params, $formName)) {
            // If no data loaded (API case), try loading with empty form name
            $this->load($params, '');
        }

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            $query->where('0=1');
            return $dataProvider;
        }
        // Filtering
        $query->andFilterWhere(['status' => $this->status])
            ->andFilterWhere(['priority' => $this->priority]);

        if ($this->due_date_from) {
            $query->andWhere(['>=', 'due_date', $this->due_date_from]);
        }
        if ($this->due_date_to) {
            $query->andWhere(['<=', 'due_date', $this->due_date_to]);
        }
        if ($this->keyword) {
            $query->andWhere(['like', 'title', $this->keyword]);
        }
        /* Supports:
            ?tag=3
            ?tag=3,4
            ?tag[]=3&tag[]=4
            ?tag=urgent
            ?tag=urgent,bug
        */
        if ($this->tag) {
             $tags = is_array($this->tag) ? $this->tag : explode(',', $this->tag);

            $query->andFilterWhere([
                'or',
                ['in', 'tags.id', $tags],
                ['in', 'tags.name', $tags],
            ]);
        }
        $query->groupBy('t.id');
       
        return $dataProvider;
    }
}
