<?php
namespace verbb\formie\elements\actions;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;

class SetSubmissionSpam extends ElementAction
{
    // Properties
    // =========================================================================

    public ?string $spam = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set spam');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('formie/_components/actions/mark-spam/trigger');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();

        /** @var Submission[] $elements */
        $elements = $query->all();
        $failCount = 0;

        foreach ($elements as $element) {
            // Unfortunately, we need to fetch the submission _again_ to ensure custom fields are grabbed. This is because we can't query
            // across multiple content tables from the "All Forms" option.
            $element = Submission::find()->uid($element->uid)->one();

            $element->isSpam = $this->spam === 'markSpam';

            if ($elementsService->saveElement($element) === false) {
                Formie::error('Unable to set spam status: {error}', ['error' => Json::encode($element->getErrors())]);

                // Validation error
                $failCount++;
            }
        }

        // Did all of them fail?
        if ($failCount === count($elements)) {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Could not update spam state due to a validation error.'));
            } else {
                $this->setMessage(Craft::t('app', 'Could not update spam state due to validation errors.'));
            }

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Spam state updated, with some failures due to validation errors.'));
        } else {
            $this->setMessage(Craft::t('app', 'Spam state updated.'));
        }

        return true;
    }
}
