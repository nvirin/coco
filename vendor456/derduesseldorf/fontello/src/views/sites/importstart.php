<!DOCTYPE html>
<html>
    <!-- Example file for Fontelloimporter -->
    <head>
        <title>Fontello - Importer</title>
        <?php echo Fontello::styles() ?>
    </head>
    <body>
        <div class="fontello-importer">
            <?php if(isset($configFile)): ?>
                <form name="fontelloimporter" action="<?php echo route('fontello.run.import') ?>" method="post" enctype="application/x-www-form-urlencoded">
                    <?php echo Form::token(); ?>
                    <p><strong>Filename: </strong> <em><?php echo $configFile ?></em></p>
                    <p><strong>Last used Session: <em><?php echo $lastUsedSession ?></strong></p>
                    <p><strong>Start Import:</strong> <button type="submit" id="submitButton">Import</button></p>
                </form>
                    <?php if($hasSession): ?>
                        <p><a href="http://fontello.com/<?php echo $fontelloSessionId ?>" target="_blank">Select on Fontello</a></p>
                    <?php endif ?>
            <?php endif ?>
        </div>
    </body>
</html>