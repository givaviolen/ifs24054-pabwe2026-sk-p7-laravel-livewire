<div>
    <form wire:submit.prevent="authenticate">
        <input type="text" id="login-username" wire:model="username" placeholder="Username / Email">
        <input type="password" id="login-password" wire:model="password" placeholder="Password">
        <button type="submit" id="login-submit">Login</button>
    </form>
</div>
