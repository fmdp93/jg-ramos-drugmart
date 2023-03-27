<?php

namespace App\Http\Traits;

trait SearchTrait
{

    private function dateFilterSetFrom(&$from)
    {
        $date_filter = request()->input('date_filter');

        switch ($date_filter) {
            case 'daily':
                $dt = new \DateTime();
                $from = $dt->format('Y-m-d') . " 00:00:00";
                break;
            case 'weekly':
                $dt = new \DateTime('monday this week');
                $from = $dt->format('Y-m-d') . " 00:00:00";
                break;
            case 'monthly':
                $dt = new \DateTime('first day of this month');
                $from = $dt->format('Y-m-d') . " 00:00:00";
                break;
            case 'yearly':
                $dt = new \DateTime('first day of January');
                $from = $dt->format('Y-m-d') . " 00:00:00";
                break;
        }
    }

    private function dateFilterSetTo(&$to)
    {
        $date_filter = request()->input('date_filter');
        $dt = new \DateTime();

        switch ($date_filter) {
            case 'daily':
                $dt = new \DateTime();
                $to = $dt->format('Y-m-d') . " 23:59:59";
                break;
            case 'weekly':
                $dt = new \DateTime('sunday this week');
                $to = $dt->format('Y-m-d') . " 23:59:59";
                break;
            case 'monthly':
                $dt = new \DateTime('last day of this month');
                $to = $dt->format('Y-m-d') . " 23:59:59";
                break;
            case 'yearly':
                $dt = new \DateTime('last day of December');
                $to = $dt->format('Y-m-d') . " 23:59:59";
                break;
        }
    }

    public static function setWhereSearch(
        $original_query,
        $search,
        array $exact_fields,
        array $like_fields
    ) {

        $likes_query = clone ($original_query);

        $likes_query->x = 'likes_x';
        $original_query->x = 'orig_x';

        $original_query->when($search, function ($query) use ($exact_fields, $search) {
            $query->where(
                function ($query) use ($exact_fields, $search) {
                    foreach ($exact_fields as $field => $operator) {
                        $query = $query->orWhere($field, $operator, $search);
                    }
                }
            );
        });

        // returns exact match first e.g. item_code or product_name        
        if (count($original_query->get()) > 0) {
            return $original_query;
        }

        // return $likes_query;

        $likes_query->when($search, function ($query) use ($like_fields, $search) {
            $query->where(function ($query) use ($like_fields, $search) {
                foreach ($like_fields as $field) {
                    $query = $query->orWhere($field, 'LIKE', "%$search%");
                    // echo $field . ' ';
                    // die();
                }
            });
        });
        // echo 'query 2';
        // die();
        return $likes_query;
    }
}
