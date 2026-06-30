<?php

namespace Modules\Sirsoft\Page\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Sirsoft\Page\Models\PageAttachment;

/**
 * 페이지 첨부파일 API 리소스
 */
class PageAttachmentResource extends BaseApiResource
{
    /**
     * URL 컨텍스트 ('admin' 또는 'public').
     *
     * 공개 라우트는 발행 가드가 있어 미발행 페이지 첨부를 차단하므로,
     * 관리자 응답은 'admin' 컨텍스트로 발행 가드 없는 라우트 URL을 사용한다.
     */
    protected string $urlContext = 'public';

    /**
     * URL 컨텍스트를 지정한 리소스 배열을 생성합니다.
     *
     * 각 첨부파일을 컨텍스트가 주입된 개별 리소스로 변환합니다.
     * (컬렉션 헬퍼는 개별 item에 컨텍스트를 전달하지 못하므로 직접 매핑)
     *
     * @param  iterable<int, PageAttachment>|null  $attachments  첨부파일 목록
     * @param  string  $context  'admin' 또는 'public'
     * @return array<int, self>
     */
    public static function collectionFor($attachments, string $context): array
    {
        return Collection::make($attachments ?? [])
            ->map(fn ($attachment) => (new self($attachment))->withContext($context))
            ->all();
    }

    /**
     * URL 컨텍스트를 설정합니다.
     *
     * @param  string  $context  'admin' 또는 'public'
     * @return $this
     */
    public function withContext(string $context): self
    {
        $this->urlContext = $context;

        return $this;
    }

    /**
     * 리소스를 배열로 변환합니다.
     *
     * @param  Request  $request  HTTP 요청 객체
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hash' => $this->hash,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'collection' => $this->collection,
            'order' => $this->order,
            'is_image' => $this->isImage(),
            'download_url' => $this->resource->downloadUrlFor($this->urlContext),
            'preview_url' => $this->resource->previewUrlFor($this->urlContext),
            'created_at' => $this->created_at
                ? $this->formatDateTimeStringForUser($this->created_at)
                : null,
        ];
    }
}
