<?php
namespace Modules\Jurnal\Models;
use CodeIgniter\Model;

class JurnalModel extends Model
{
    protected $table = 'data_2025_trans';

    private function _buildDataTableQuery($searchValue = null)
    {
        $builder = $this->db->table($this->table . ' as a');
        $builder->select('a.*, b.nama_acc');
        $builder->join('data_2025_sub as b', 'a.trans_no_acc = b.no_acc', 'left');

        if ($searchValue) {
            $builder->groupStart();
            $builder->like('a.trans_id', $searchValue);
            $builder->orLike('a.trans_no_trans', $searchValue);
            $builder->orLike('a.trans_ket', $searchValue);
            $builder->orLike('b.nama_acc', $searchValue);
            $builder->groupEnd();
        }
        return $builder;
    }

    public function getDataTable($start, $length, $searchValue, $order)
    {
        $builder = $this->_buildDataTableQuery($searchValue);

        if ($order && count($order)) {
            $column = $order[0]['column'];
            $dir = $order[0]['dir'];
            // Menambahkan trans_id ke kolom yang bisa di-sort
            $sortableColumns = ['trans_id', 'trans_tgl', 'trans_no_trans', 'trans_ket', 'nama_acc', 'trans_DB', 'trans_CR'];
            $builder->orderBy($sortableColumns[$column], $dir);
        } else {
            // Urutan default: ID terbaru di atas
            $builder->orderBy('a.trans_id', 'DESC');
        }

        if ($length != -1) {
            $builder->limit($length, $start);
        }
        return $builder->get()->getResultArray();
    }

    public function countFilteredData($searchValue)
    {
        $builder = $this->_buildDataTableQuery($searchValue);
        return $builder->countAllResults();
    }

    public function countAllData()
    {
        return $this->db->table($this->table)->countAllResults();
    }
}