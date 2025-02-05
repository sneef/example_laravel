<?php

namespace App\Repositories;

use App\DTO\{
	ClientCopmanyDTO,
	ClientDTO
};
use App\Models\{
    Client,
    ClientDetails
};
use App\Interfaces\ClientRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * Добавление клиента
     * 
     * @pararm ClientDTO $clientData - запрос с данными для добавления инф-ции о клиенте
     * @pararm array $clientDetails - запрос с данными для добавления деталей клиента
     * 
     * @return Client $client|false - созданный объект типа Client или false если не был созданы/сохранены данные объекта
     */
    public function create(ClientDTO $clientData, ClientCopmanyDTO $clientDetails)
    {
        return DB::transaction(function () use ($clientData, $clientDetails) {

            $client = Client::create($clientData);

            if ($client) {
                $clientDetails['client_id'] = $client->id;
            }

            ClientDetails::create($clientDetails);

            return $client;

        }, 4);
    }

    /**
     * Обновление информации о клиенте
     * 
     * @param int $clientId - идентификатор клиента
     * @param int $companyId - индентификатор компании
     * @param array $clientData - массив для обновления общих данных клиента
     * @param array $clientDetails - массив для обновления дополнительных данных клиента
     * @return bool - успешно ли обноввлено
     */
    public function update($clientId, $companyId, ClientDTO $clientData, ClientCopmanyDTO $clientDetails) : bool
    {
        $isClientUpdated = DB::transaction(function () use ($clientId, $companyId, $clientData, $clientDetails) {

            return Client::updateOrInsert(['id' => $clientId, 'company_id' => $companyId], $clientData)
                && ClientDetails::updateOrInsert(['client_id' => $clientId], $clientDetails);
  
        }, 4);

        return $isClientUpdated;
    }

    /**
     * Удаляет клиента
     * 
     * @param int $companyId - индентификатор компании
     * @param int $clientId - идентификатор клиента
     * @return bool - успешно ли удалено
     * 
     */
    public function delete($companyId = 0, $clientId = 0) : bool
    {
        $client = Client::where('id', $clientId)->where('company_id', $companyId)->first();

        return $client && $client->delete();
    }

    /**
     * Получение информации о клиенте с деталями
     * 
     * @param int $companyId - индентификатор компании
     * @param int $clientId - идентификатор клиента
     * @return ActiveRecord
     */
    public function getClient($companyId = 0, $clientId = 0)
    {
        return Client::with('clientDetails')->where('id', $clientId)->where('company_id', $companyId)->first();
    }

    /**
     * Фильтрация клиентов для таблицы
     * 
     * @param int $userId - идентификатор пользователя
     * @param int $companyId - индентификатор компании
     * @param array $columns - столбцы со статусом сортировки
     * @param array $order - поле для сортировки и направление
     * @param array $search - искомое слово
     * @param int $start - отступ для пагинации
     * @param int $length - количество подтягиваемых элементов
     * @param int $draw - счетчик запроса проприсовок
     * @return array - список элементов
     */
    public function filteredList(
        $userId = 0,
        $companyId = 0,
        array $columns = [],
        array $order = [],
        array $search = [],
        $start = 0,
        $length = 10,
        $draw = 0
    ) {
        $clientsDataQuery = Client::query();
        $clientsFilteredCountQuery = Client::query();
        $clientsTotalQuery = Client::query();

        $clientsDataQuery->select([
            'clients.id',
            'clients.legal_type',
            'clients.contact_name',
            'clients.firstname',
            'clients.lastname',
            'clients.patronymic',
            'clients.created_at',
            'clients_details.mobilephone_code',
            'clients_details.mobilephone'
        ]);

        $clientsDataQuery->join('clients_details', 'clients_details.client_id', '=', 'clients.id');
        $clientsDataQuery->where('company_id', $companyId);

        $clientsFilteredCountQuery->join('clients_details', 'clients_details.client_id', '=', 'clients.id');
        $clientsFilteredCountQuery->where('company_id', $companyId);

        $clientsTotalQuery->where('company_id', $companyId);

        if (
            is_array($order)
            && !empty($order)
        ) {
            $orderIndex = array_key_exists(0, $order) && array_key_exists('column', $order[0]) ? $order[0]['column'] : -1;

            if (array_key_exists($orderIndex, $columns)) {
                $orderField = $columns[$orderIndex]['data'];
                $orderDir = $order[0]['dir'];

                $orderField = match($orderField) {
                    'created_at' => 'created_at',
                    'name' => DB::raw("CONCAT(contact_name, lastname, ' ', firstname , ' ', patronymic)"),
                    'mobilephone' => DB::raw("CONCAT(mobilephone_code, mobilephone)")
                };

                $clientsDataQuery->orderBy($orderField, $orderDir);
            }
        }

        if (
            is_array($search)
            && array_key_exists('value', $search)
        ) {
            $clientsDataQuery->where(function($q) use ($search) {
                $q->orWhere(
                    DB::raw("TO_CHAR(created_at, 'dd.mm.yyyy')"),
                    'LIKE',
                    "%" . $search['value'] . "%"
                );

                $q->orWhere(
                    DB::raw("CONCAT(contact_name, lastname, ' ', firstname , ' ', patronymic)"),
                    'ILIKE',
                    "%" . $search['value'] . "%"
                );

                $q->orWhere(
                    DB::raw("CONCAT(mobilephone_code, mobilephone)"),
                    'LIKE',
                    "%" . $search['value'] . "%"
                );

                $q->orWhere(
                    DB::raw("CONCAT(mobilephone_code, mobilephone)"),
                    'LIKE',
                    "%" . $search['value'] . "%"
                );
            });

            $clientsFilteredCountQuery->where(function($q) use ($search) {
                $q->orWhere(
                    DB::raw("TO_CHAR(created_at, 'dd.mm.yyyy')"),
                    'LIKE',
                    "%" . $search['value'] . "%"
                );

                $q->orWhere(
                    DB::raw("CONCAT(contact_name, lastname, ' ', firstname , ' ', patronymic)"),
                    'ILIKE',
                    "%" . $search['value'] . "%"
                );

                $q->orWhere(
                    DB::raw("CONCAT(mobilephone_code, mobilephone)"),
                    'LIKE',
                    "%" . $search['value'] . "%"
                );

                $q->orWhere(
                    DB::raw("CONCAT(mobilephone_code, mobilephone)"),
                    'LIKE',
                    "%" . $search['value'] . "%"
                );
            });
        }

        $clientsDataQuery->offset($start);
        $clientsDataQuery->limit($length);

        $recordsFiltered = $clientsFilteredCountQuery->count();

        return [
            'draw' => $draw,
            'recordsTotal' => $clientsTotalQuery->count(),
            'recordsFiltered' => $recordsFiltered,
            'data' => $clientsDataQuery->get()
        ];
    }
}