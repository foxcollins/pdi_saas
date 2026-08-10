<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $path = $file->storeAs(
            'tenants/'.tenant()->id,
            Str::uuid().'.'.$file->extension(),
            'public'
        );

        $media = Media::create([
            'file_key' => $path,
            'url' => '/storage/'.$path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt' => $request->input('alt'),
        ]);

        return response()->json([
            'ok' => true,
            'media' => [
                'id' => $media->id,
                'url' => $media->url,
                'alt' => $media->alt,
            ],
        ]);
    }
}
