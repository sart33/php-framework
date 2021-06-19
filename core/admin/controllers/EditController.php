<?php


namespace core\admin\controllers;


class EditController extends BaseAdmin
{
    protected function inputData() {

        if(empty($this->userId)) $this->execBase();

    }

    protected function checkOldAlias($id) {

        $tables = $this->model->ShowTables();

        if(in_array('old_alias', $tables)) {

            $oldAliasArr = $this->model->get($this->table, [
                'fields' => ['alias'],
                'where' => [$this->columns['id_row'] => $id]
            ])[0]['alias'];
            if(isset($oldAliasArr[0]['alias'])) {
                $oldAlias = $oldAliasArr[0]['alias'];
            }

            if(!empty($oldAlias) && $oldAlias !== $_POST['alias']) {

                $this->model->delete('old_alias', [
                    'where' => ['alias' => $oldAlias, 'table_name' => $this->table]
                ]);

                $this->model->delete('old_alias', [
                    'where' => ['alias' => $_POST['alias'], 'table_name' => $this->table]
                ]);

                $this->model->add('old_alias', [
                    'fields' => ['alias' => $oldAlias, 'table_name' => $this->table, 'table_id' => $id]
                ]);

            }
        }
    }

}