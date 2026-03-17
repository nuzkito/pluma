@extends('layout')

@section('title', 'Pages')

@section('content')
@if($pages->isEmpty())
    <p class="text-gray-500">No pages yet. Create your first page.</p>
@else
    <table class="w-full border-collapse bg-white rounded overflow-hidden">
        <thead>
            <tr class="bg-gray-200 text-left">
                <th class="p-3">Title</th>
                <th class="p-3">Status</th>
                <th class="p-3">Created</th>
                <th class="p-3">Published</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pages as $page)
            <tr class="border-t border-gray-200">
                <td class="p-3">
                    <a href="{{ route('pages.edit', $page->path) }}" class="text-blue-600 hover:underline">{{ $page->title }}</a>
                </td>
                <td class="p-3">
                    @if($page->isPublished())
                        <span class="badge badge-published">Published</span>
                    @else
                        <span class="badge badge-draft">Draft</span>
                    @endif
                </td>
                <td class="p-3">{{ $page->created_at->format('Y-m-d H:i') }}</td>
                <td class="p-3">{{ $page->published_at?->format('Y-m-d H:i') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endsection
