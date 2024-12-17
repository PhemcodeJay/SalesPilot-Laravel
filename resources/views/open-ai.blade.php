<form method="POST" action="/generate-text">
    @csrf
    <label for="prompt">Enter your prompt:</label>
    <textarea name="prompt" id="prompt" rows="4"></textarea>
    <button type="submit">Generate</button>
</form>
