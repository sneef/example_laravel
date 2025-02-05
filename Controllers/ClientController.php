<?php

namespace App\Http\Controllers\Api;

use App\DTO\{
	ClientCompanyDTO,
	ClientDTO
};
use App\Http\Controllers\Controller;
use App\Interfaces\ClientRepositoryInterface;
use App\Http\Requests\Api\{
    ClientCreateRequest,
    ClientDeleteRequest,
    ClientFilteredListRequest,
    ClientRequest,
    ClientUpdateRequest
};
use App\Models\{
    Client,
    ClientDetails
};
use App\Repositories\ClientRepository;
use App\Services\Api\BaseApi;
use Illuminate\Http\{
    Request,
    Response
};

class ClientController extends Controller
{
    private ClientRepositoryInterface $clientRepository;

    public function __construct(ClientRepositoryInterface $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * Добавление клиента
     * 
     * @param ClientCreateRequest $request - валидатор с данными
     * @return Response - json-ответ со стасусом добавления
     */
    public function create(ClientCreateRequest $request)
    {
        $userId          = $request->input('userId', 0);
        $companyId       = $request->input('companyId', 0);
        $legalType       = $request->input('legalType', 0);
        $contactName     = $request->input('contactName', '');
        $firstname       = $request->input('firstname', '');
        $lastname        = $request->input('lastname', '');
        $patronymic      = $request->input('patronymic', '');
        $mobilephoneCode = $request->input('mobilephoneCode', '');
        $mobilephone     = $request->input('mobilephone', '');
        $localPhone      = $request->input('localPhone', '');
        $telegram        = $request->input('telegram', '');
        $whatsapp        = $request->input('whatsapp', '');
        $skype           = $request->input('skype', '');
        $description     = $request->input('description', '');
		
		$clientDTO = new ClientDTO(
			user_id: $userId,
            company_id: $companyId,
            legal_type: $legalType,
            contact_name: $legalType == Client::LEGAL_TYPE_PHYSICAL ? null : $contactName,
            firstname: $legalType != Client::LEGAL_TYPE_PHYSICAL ? null : $firstname,
            lastname: $legalType != Client::LEGAL_TYPE_PHYSICAL ? null : $lastname,
            patronymic: $legalType != Client::LEGAL_TYPE_PHYSICAL ? null : $patronymic
		);
		
		$clientCompanyDTO = new ClientCompanyDTO(
			mobilephone_code: $mobilephoneCode,
            mobilephone: $mobilephone,
            local_phone: $localPhone,
            telegram: $telegram,
            whatsapp: $whatsapp,
            skype: $skype,
            description: $description
		);

        $clientCreated = $this->clientRepository->create($clientDTO,
            [
                'mobilephone_code' => $mobilephoneCode,
                'mobilephone' => $mobilephone,
                'local_phone' => $localPhone,
                'telegram' => $telegram,
                'whatsapp' => $whatsapp,
                'skype' => $skype,
                'description' => $description
            ]
        );

        if($clientCreated) {
            return response()->json([
                    'id' => $clientCreated->id
                ], Response::HTTP_CREATED);
        }

        return response()->json([
            'message' => __('messages.response_failed')
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Обновление информации о клиенте
     * 
     * @param int $clientId - идентификатор клиента
     * @param ClientUpdateRequest $request - валидатор с данными
     * @return Response
     */
    public function update($clientId, ClientUpdateRequest $request)
    {
        $userId          = $request->input('userId', 0);
        $companyId       = $request->input('companyId', 0);
        $legalType       = $request->input('legalType', 0);
        $contactName     = $request->input('contactName', '');
        $firstname       = $request->input('firstname', '');
        $lastname        = $request->input('lastname', '');
        $patronymic      = $request->input('patronymic', '');
        $mobilephoneCode = $request->input('mobilephoneCode', '');
        $mobilephone     = $request->input('mobilephone', '');
        $localPhone      = $request->input('localPhone', '');
        $telegram        = $request->input('telegram', '');
        $whatsapp        = $request->input('whatsapp', '');
        $skype           = $request->input('skype', '');
        $description     = $request->input('description', '');
		
		$clientDTO = new ClientDTO(
			user_id: $userId,
            company_id: $companyId,
            legal_type: $legalType,
            contact_name: $legalType == Client::LEGAL_TYPE_PHYSICAL ? null : $contactName,
            firstname: $legalType != Client::LEGAL_TYPE_PHYSICAL ? null : $firstname,
            lastname: $legalType != Client::LEGAL_TYPE_PHYSICAL ? null : $lastname,
            patronymic: $legalType != Client::LEGAL_TYPE_PHYSICAL ? null : $patronymic
		);
		
		$clientCompanyDTO = new ClientCompanyDTO(
			mobilephone_code: $mobilephoneCode,
            mobilephone: $mobilephone,
            local_phone: $localPhone,
            telegram: $telegram,
            whatsapp: $whatsapp,
            skype: $skype,
            description: $description
		);

        $isClientUpdated = $this->clientRepository->update($clientId, $companyId, $clientDTO, $clientCompanyDTO);

        return response()->json([
                'status' => $isClientUpdated ? BaseApi::STATUS_SUCCESS : BaseApi::STATUS_FAILED
        ], Response::HTTP_OK);
    }

    /**
     * Удаление клиента
     * 
     * @param int $clientId - идентификатор клиента
     * @param ClientDeleteRequest - входящие данные с идентификатором компании
     * @return Response
     */
    public function delete($clientId, ClientDeleteRequest $request)
    {
        $companyId = $request->input('companyId', 0);

        $isDeleted = $this->clientRepository->delete($companyId, $clientId);

        return response()->json([
            'status' => $isDeleted ? BaseApi::STATUS_SUCCESS : BaseApi::STATUS_FAILED
        ], Response::HTTP_OK);
    }

    /**
     * Получение информации о клиенте
     * 
     * @param int $clientId - идентификатор клиента
     * @param ClientRequest $request - валидатор
     * @return Response
     */
    public function client($clientId, ClientRequest $request)
    {
        $companyId = $request->input('companyId', 0);

        $clientInfo = $this->clientRepository->getClient($companyId, $clientId);

        return response()->json($clientInfo);
    }

    /**
     * Вывод фильтрованного списка клиентов
     * 
     * @param ClientFilteredListRequest $request - валидатор с данными
     * @return Response - список клиентов, количество записей и количество отфильтрованных записей
     */
    public function filteredList(ClientFilteredListRequest $request)
    {
        $userId           = $request->input('userId', 0);
        $companyId        = $request->input('companyId', 0);
        $columns          = $request->input('columns', []);
        $order            = $request->input('order', []);
        $search           = $request->input('search', []);
        $start            = $request->input('start', 0);
        $length           = $request->input('length', 10);
        $draw             = $request->input('draw', 0);

        $length = min($length, config('app.datatables.maxRowsPerPage'));

        return response()->json($this->clientRepository->filteredList(
            $userId,
            $companyId,
            $columns,
            $order,
            $search,
            $start,
            $length,
            $draw
        ), Response::HTTP_OK);
    }
}
