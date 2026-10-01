<?php
class Data {
    private string $data;
    public function __construct()
    {
        throw new \Exception('Data não implementada');
    }

    public function getData(){
        return $this->data;
    }

    public function setData(string $data){
        $this->data = $data;
    }
}