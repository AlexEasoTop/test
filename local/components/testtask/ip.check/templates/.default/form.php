<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

?>

<div>
    <h1>Проверка IP-адреса</h1>

    <div class="row">
        <form name="check-ip" method="post">
            <div class="row">
                <label for="ip">Введите IP-адрес:</label>
                <input type="text" name="ip" id="ip" placeholder="Введите IP-адрес...">
            </div>
            <button type="submit" id="submit">Проверить</button>
        </form>
    </div>
    <div class="row">
        <p class="error-text hidden"></p>
        <div class="geodata hidden"></div>
    </div>
</div>
