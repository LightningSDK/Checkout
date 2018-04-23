<h2>Where should we ship it?</h2>
<form data-abide method="post" action="/checkout">
    <?= \Lightning\Tools\Form::renderTokenInput(); ?>
    <input type="hidden" name="action" value="shipping">
    <div class="row">
        <div class="">
            Name:
        </div>
        <div class="column small-12 medium-6">
            <input name="name" required>
        </div>
    </div>
    <div class="row">
        <div class="">
            Address:
        </div>
        <div class="column small-12 medium-6">
            <input name="address" required>
        </div>
    </div>
    <div class="row">
        <div class="">
            Address (cont):
        </div>
        <div class="column small-12 medium-6">
            <input name="address2">
        </div>
    </div>
    <div class="row">
        <div class="">
            City:
        </div>
        <div class="column small-12 medium-6">
            <input name="city" required>
        </div>
    </div>
    <div class="row">
        <div class="">
            State:
        </div>
        <div class="column small-12 medium-6">
            <?= \Lightning\View\Field\Location::statePop('name', 'required'); ?>
        </div>
    </div>
    <div class="row">
        <div class="">
            Postal Code:
        </div>
        <div class="column small-12 medium-6">
            <input name="zip" required>
        </div>
    </div>
    <div class="row">
        <div class="">
            Country:
        </div>
        <div class="column small-12 medium-6">
            <?= \Lightning\View\Field\Location::countryPop('name', 'required'); ?>
        </div>
    </div>
    <div class="row">
        <div class="column text-right">
            <input type="submit" name="submit" value="Next">
        </div>
    </div>
</form>
