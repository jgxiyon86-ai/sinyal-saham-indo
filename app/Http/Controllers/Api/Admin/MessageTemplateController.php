<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MessageTemplateController extends Controller
{
    private array $religions;

    public function __construct()
    {
        $this->religions = array_keys(config('religions.options', []));
    }

    public function index(Request $request): JsonResponse
    {
        $query = MessageTemplate::query()->latest();

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->string('event_type'));
        }

        if ($request->filled('religion')) {
            $query->where('religion', $request->string('religion'));
        }

        return response()->json([
            'templates' => $query->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', Rule::in(['birthday', 'holiday', 'general'])],
            'religion' => [
                'nullable',
                Rule::in($this->religions),
                Rule::requiredIf($request->input('event_type') === 'holiday'),
            ],
            'content' => ['required', 'string'],
            'image_url' => ['nullable', 'url'],
            'image_file' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('image_file')) {
            $data['image_url'] = $this->storeImageAndGetUrl($request->file('image_file'));
        }

        unset($data['image_file']);

        $template = MessageTemplate::create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Template berhasil dibuat.',
            'template' => $template,
        ], 201);
    }

    public function show(MessageTemplate $messageTemplate): JsonResponse
    {
        return response()->json([
            'template' => $messageTemplate,
        ]);
    }

    public function update(Request $request, MessageTemplate $messageTemplate): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', Rule::in(['birthday', 'holiday', 'general'])],
            'religion' => [
                'nullable',
                Rule::in($this->religions),
                Rule::requiredIf($request->input('event_type') === 'holiday'),
            ],
            'content' => ['required', 'string'],
            'image_url' => ['nullable', 'url'],
            'image_file' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($request->hasFile('image_file')) {
            $data['image_url'] = $this->storeImageAndGetUrl($request->file('image_file'));
        }

        unset($data['image_file']);

        $messageTemplate->update($data);

        return response()->json([
            'message' => 'Template berhasil diupdate.',
            'template' => $messageTemplate,
        ]);
    }

    public function destroy(MessageTemplate $messageTemplate): JsonResponse
    {
        $messageTemplate->delete();

        return response()->json([
            'message' => 'Template berhasil dihapus.',
        ]);
    }

    private function storeImageAndGetUrl(UploadedFile $file): string
    {
        $path = $file->store('wa-template-images', 'public');

        return Storage::disk('public')->url($path);
    }
}
