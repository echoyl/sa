<?php

namespace Echoyl\Sa\Helpers;

use Echoyl\Sa\Exceptions\AException;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait ApiResponse
{
    /**
     * 成功
     *
     * @param  null  $data
     * @param  array  $codeResponse
     */
    public function success($data = null, $codeResponse = ResponseEnum::HTTP_OK): JsonResponse
    {
        return $this->jsonResponse('success', $codeResponse, $data, null);
    }

    public function successMsg($msg = '', $data = null): JsonResponse
    {
        [$code] = ResponseEnum::HTTP_OK;

        return $this->success($data, [$code, $msg]);
    }

    public function failMsg($msg = ''): JsonResponse
    {
        [$code] = ResponseEnum::HTTP_ERROR_DEFAULT;

        return $this->fail([$code, $msg]);
    }

    public function list($data, $total, $search = [])
    {
        [$code, $message] = ResponseEnum::HTTP_OK;

        return response()->json([
            'status' => 'success',
            'code' => $code,
            'msg' => $message,
            'search' => $search,
            'total' => $total,
            'count' => $total,
            'data' => $data ?? null,
            'error' => null,
            'success' => true,
        ]);

    }

    /**
     * 失败
     *
     * @param  array  $codeResponse
     * @param  null  $data
     * @param  null  $error
     */
    public function fail($codeResponse = ResponseEnum::HTTP_ERROR, $data = null, $error = null): JsonResponse
    {
        return $this->jsonResponse('fail', $codeResponse, $data, $error);
    }

    public function notification($notification, $data = null)
    {
        [$code] = ResponseEnum::HTTP_OK;

        return response()->json([
            'status' => 'success',
            'code' => $code,
            'notification' => is_array($notification) ? json_encode($notification) : $notification,
            'data' => $data ?? null,
            'error' => null,
            'success' => true,
        ]);
    }

    /**
     * json响应
     */
    protected function jsonResponse($status, $codeResponse, $data, $error): JsonResponse
    {
        [$code, $message] = $codeResponse;

        return response()->json([
            'status' => $status,
            'code' => $code,
            'msg' => $message,
            'data' => $data ?? null,
            'error' => $error,
            'success' => true,
        ]);
    }

    /**
     * 成功分页返回
     */
    protected function successPaginate($page): JsonResponse
    {
        return $this->success($this->paginate($page));
    }

    private function paginate($page)
    {
        if ($page instanceof LengthAwarePaginator) {
            return [
                'total' => $page->total(),
                'page' => $page->currentPage(),
                'limit' => $page->perPage(),
                'pages' => $page->lastPage(),
                'list' => $page->items(),
            ];
        }
        if ($page instanceof Collection) {
            $page = $page->toArray();
        }
        if (! is_array($page)) {
            return $page;
        }
        $total = count($page);

        return [
            'total' => $total, // 数据总数
            'page' => 1, // 当前页码
            'limit' => $total, // 每页的数据条数
            'pages' => 1, // 最后一页的页码
            'list' => $page, // 数据
        ];
    }

    /**
     * 业务异常返回
     *
     * @throws BusinessException
     */
    public function throwAException(array $codeResponse = ResponseEnum::HTTP_ERROR, string $info = '')
    {
        throw new AException($codeResponse, $info);
    }
}
