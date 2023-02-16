<?php

namespace App\Command;

use App\Indexer\Librarians\Index;
use App\Indexer\Librarians\Librarian;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class EditLibrarianCommand extends Command
{
    // How we call this command
    protected static $defaultName = 'librarians:edit';

    // The search index.
    private Index $index;

    // The console IO.
    private SymfonyStyle $io;

    // Command success states
    public const SUCCESS = 0;
    public const FAILURE = 1;

    public function __construct(Index $librarians_index)
    {
        parent::__construct();
        $this->index = $librarians_index;
    }

    /**
     * Add more options and whatnot
     */
    protected function configure(): void
    {
        $this->setDescription('Edit a librarian')
            ->addArgument('id', InputArgument::REQUIRED, 'LibGuides ID of librarian')
            ->addOption('index-name', null, InputOption::VALUE_REQUIRED, 'Name for the index, if not librarians')
            ->setHelp('This command will also create the librarian if they do not already exist in the index.');
    }

    /**
     * Run the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int 0 on success, 1 on failure
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Load the librarian. If the librarian isn't in the index, create a new one.
        $id = $input->getArgument('id');
        try {
            $librarian = $this->index->getLibrarian($id);
            $this->io->writeln("Found librarian with LibGuides ID $id");
        } catch (\Exception $e) {
            if (!$this->confirmCreateNewLibrarian($id)) {
                return self::SUCCESS;
            }
            $librarian = $this->buildNewLibrarian($id);
        }

        // Loop around while the user wants to edit stuff.
        while ($librarian = $this->editFieldDialog($librarian)) {
            $this->index->update($librarian);
        }

        return self::SUCCESS;
    }

    /**
     * Show a Librarians' current state
     *
     * @param Librarian $librarian the Librarian to show
     * @return void
     */
    private function showLibrarian(Librarian $librarian): void
    {
        $table_rows = [
            ['Id', $librarian->getId()],
            ['Email', $librarian->getEmail()],
            ['First name', $librarian->getFirstName()],
            ['Last name', $librarian->getLastName()],
            ['Title', $librarian->getTitle()],
            ['Image', $librarian->getImage()],
            ['Subjects', join("\n", $librarian->getSubjects())],
            ['Taxonomy', join("\n", $librarian->getTaxonomy())],
            ['Terms', join("\n", $librarian->getTerms())]
        ];

        $table = new Table($this->io);
        $table->setHeaders(['Field', 'Value'])->setRows($table_rows);
        $table->render();
    }

    /**
     * Query the user about what field to edit and perform the edit
     *
     * @param Librarian $librarian the librarian to edit
     * @return Librarian|null the Librarian after editing
     */
    private function editFieldDialog(Librarian $librarian): ?Librarian
    {
        $this->showLibrarian($librarian);

        // Save the current values, because we might be creating a new Librarian in a bit.
        $new_values = [
            'email'      => $librarian->getEmail(),
            'first_name' => $librarian->getFirstName(),
            'last_name'  => $librarian->getLastName(),
            'title'      => $librarian->getTitle(),
            'image'      => $librarian->getImage(),
            'subjects'   => $librarian->getSubjects(),
            'taxonomy'   => $librarian->getTaxonomy(),
            'terms'      => $librarian->getTerms()
        ];

        // Accept all the editble fields as options, plus add a couple.
        $options = array_keys($new_values);
        $options[] = 'delete';
        $options[] = 'quit';

        $edit_field_question = new ChoiceQuestion('Modify', $options);
        $response = $this->io->askQuestion($edit_field_question);

        switch ($response) {

            // If we're deleting or quiting, return null
            case 'delete':
                $this->deleteLibrarian($librarian);
            case 'quit':
                return null;

            // If we're editing a single-value field, just ask for the new value.
            case 'email':
            case 'first_name':
            case 'last_name':
            case 'title':
            case 'image':
                $new_values[$response] = $this->io->askQuestion(new Question($response));
                break;

            // If we're asking for a multi-value field, things are more complicated.
            case 'subjects':
            case 'taxonomy':
            case 'terms':
                $new_values[$response] = $this->editList($response, $new_values[$response]);
        }

        // Build a new librarian to return. One of the values will be updated.
        return new Librarian(
            $librarian->getId(),
            $new_values['email'],
            $new_values['first_name'],
            $new_values['last_name'],
            $new_values['image'],
            $new_values['title'],
            $new_values['subjects'],
            $new_values['taxonomy'],
            $new_values['terms']
        );
    }

    /**
     * Make sure the user really wants to create a librarian
     *
     * @param string $id the id of the librarian to create
     * @return bool true if the user confirms, false otherwise
     */
    private function confirmCreateNewLibrarian(string $id): bool
    {
        $this->io->writeln("Couldn't find librarian with LibGuides id $id.");
        $create_confirmation_question = new ConfirmationQuestion("Create a new librarian?", false);
        $create_confirmation = $this->io->askQuestion($create_confirmation_question);
        if (!$create_confirmation) {
            $this->io->writeln("Exiting without creating new librarian.");
        }
        return (bool)$create_confirmation;
    }

    /**
     * Edit a list of values
     *
     * @param string $field_name the name of the field that contains the list
     * @param string[] $values the values in the field
     * @return string[] the values of the field after any edits or deletions
     */
    private function editList(string $field_name, array $values): array
    {
        $action_question = new ChoiceQuestion("Modify $field_name", ['delete value', 'add value']);
        $action = $this->io->askQuestion($action_question);

        if ($action === 'add value') {
            $values[] = $this->io->askQuestion(new Question("New value"));
        } elseif ($action === 'delete value') {
            $values = $this->deleteListValue($values);
        }

        return $values;
    }

    /**
     * Build a new Librarian
     *
     * @param string $id the LibApps ID of the librarian to create
     * @return Librarian|null the Librarian, or null if something went horribly wrong
     */
    private function buildNewLibrarian(string $id): ?Librarian
    {
        $email_question = new Question('Email');
        $email = $this->io->askQuestion($email_question);

        $first_name_question = new Question('First name');
        $first_name = $this->io->askQuestion($first_name_question);

        $last_name_question = new Question('Last name');
        $last_name = $this->io->askQuestion($last_name_question);

        $title_question = new Question('Title');
        $title = $this->io->askQuestion($title_question);

        $image_question = new Question('Image');
        $image = $this->io->askQuestion($image_question);

        $librarian = new Librarian($id, $email, $first_name, $last_name, $image, $title, [], [], []);
        $this->index->update($librarian);

        return $librarian;
    }

    /**
     * Delete a librarian
     *
     * @param Librarian $librarian the librarian to delete
     * @return void
     */
    private function deleteLibrarian(Librarian $librarian)
    {
        $create_confirmation_question = new ConfirmationQuestion(
            "Really delete librarian with LibGuides ID {$librarian->getId()}?",
            false
        );

        if ($this->io->askQuestion($create_confirmation_question)) {
            $this->index->delete($librarian);
            $this->io->writeln("Deleted librarian");
        };
    }

    /**
     * Query for a value in a list to delete
     *
     * "Deleting" an item in an Elasticsearch list isn't really a deletion action,
     * it's a rewriting action with the deleted item omitted. This function
     * builds a list of all the items we do _not_ want deleted.
     *
     * @param string[] $values the list of values to delete from
     * @return string[] the list minus the value to be deleted
     */
    private function deleteListValue(array $values): array
    {

        // Ask the user which item to delete.
        $value_select_question = new ChoiceQuestion('Value to delete', $values);
        $value_to_delete = $this->io->askQuestion($value_select_question);

        // Filter the value to delete out of the list of values and re-index the array
        $sparse_array = array_filter($values, function ($value) use ($value_to_delete) {
            return $value != $value_to_delete;
        });
        return array_values($sparse_array);
    }
}