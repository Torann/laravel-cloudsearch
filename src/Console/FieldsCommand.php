<?php

namespace LaravelCloudSearch\Console;

use Illuminate\Support\Arr;

class FieldsCommand extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:fields';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A very basic field syncing command.';

    /**
     * CloudSearch domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * Changes made to the index.
     *
     * @var int
     */
    protected $changes = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Sanity check
        if (empty($fields = config('cloud-search.fields'))) {
            $this->error("No fields defined in the config.");
            return null;
        }

        // Ensure the system specific value is set
        $fields['searchable_type'] = 'literal';

        // Set CloudSearch domain for later
        $this->domain = $this->cloudSearcher->config('domain_name');

        $this->getOutput()->write('Syncing fields');

        // Process everything
        $this->syncCurrentFields($fields);
        $this->syncNewFields($fields);

        $this->getOutput()->writeln('<info>success</info>');
        $this->line('');

        // Check for changes
        if ($this->changes > 0) {
            $this->comment("{$this->changes} field change(s) were made to the \"{$this->domain}\" domain, these changes will not be reflected in search results until the index is rebuilt.");

            if ($this->confirm("Would you like to rebuild the index?")) {
                $this->runIndexing();
            }
        }
        else {
            $this->comment('Fields are up to date.');
        }
    }

    /**
     * Update or remove any current fields.
     *
     * @param array $fields
     */
    protected function syncCurrentFields(&$fields)
    {
        foreach($this->getFields() as $name=>$type) {

            // Was the field removed
            if (($current_type = Arr::get($fields, $name)) === null) {
                $this->deleteField($name);
            }

            // Was the field changed
            else if ($current_type !== $type) {
                $this->defineField($name, $type);
            }

            unset($fields[$name]);

            $this->getOutput()->write('.');
        }
    }

    /**
     * Sync new fields.
     *
     * @param array $fields
     */
    protected function syncNewFields($fields)
    {
        foreach($fields as $name=>$type) {
            $this->defineField($name, $type);
            $this->getOutput()->write('.');
        }
    }

    /**
     * Get all fields for the domain.
     *
     * @param array $fields
     *
     * @return array
     */
    protected function getFields(array $fields = [])
    {
        $response = $this->cloudSearcher->searchClient()->describeIndexFields([
            'DomainName' => $this->domain,
        ]);

        foreach(Arr::get($response, 'IndexFields', []) as $value) {
            $fields[Arr::get($value, 'Options.IndexFieldName')] = Arr::get($value, 'Options.IndexFieldType');
        }

        return $fields;
    }

    /**
     * Create or updates a field in the domain.
     *
     * @param string $field
     * @param string $type
     *
     * @return bool
     */
    protected function defineField($field, $type)
    {
        $response = $this->cloudSearcher->searchClient()->defineIndexField([
            'DomainName' => $this->domain,
            'IndexField' => [
                'IndexFieldName' => $field,
                'IndexFieldType' => $type,
            ],
        ]);

        // Check for success
        if ($result = Arr::get($response, '@metadata.statusCode') == 200) {
            $this->changes++;
        }

        return $result;
    }

    /**
     * Delete the given field from the domain.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function deleteField($field)
    {
        $response = $this->cloudSearcher->searchClient()->deleteIndexField([
            'DomainName' => $this->domain,
            'IndexFieldName' => $field,
        ]);

        // Check for success
        if ($result = Arr::get($response, '@metadata.statusCode') == 200) {
            $this->changes++;
        }

        return $result;
    }

    /**
     * Tells the search domain to start indexing its documents using the latest indexing options.
     */
    protected function runIndexing()
    {
        $response = $this->cloudSearcher->searchClient()->indexDocuments([
            'DomainName' => $this->domain,
        ]);

        if (Arr::get($response, '@metadata.statusCode') == 200) {
            $this->line('CloudSearch is currently rebuilding your index.');
        }
        else {
            $this->error('Something prevented the rebuild process. Log into your AWS console to find out more.');
        }
    }
}
