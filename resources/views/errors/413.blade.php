<x-layout title="Upload too large">
    <div class="mx-auto max-w-md text-center">
        <h1 class="heading text-3xl">That file is too large</h1>
        <p class="mt-4 text-muted">
            Photos can be up to {{ \App\Http\Requests\RescueRequest::MAX_IMAGE_MB }} MB.
            Please go back and pick a smaller one.
        </p>
        <button type="button" onclick="history.back()" class="btn mt-8">Go back</button>
    </div>
</x-layout>
