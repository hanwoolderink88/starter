<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':Attribute moet geaccepteerd zijn.',
    'accepted_if' => ':Attribute moet geaccepteerd zijn als :other :value is.',
    'active_url' => ':Attribute moet een geldige URL zijn.',
    'after' => ':Attribute moet een datum na :date zijn.',
    'after_or_equal' => ':Attribute moet een datum op of na :date zijn.',
    'alpha' => ':Attribute mag alleen letters bevatten.',
    'alpha_dash' => ':Attribute mag alleen letters, cijfers, streepjes en liggende streepjes bevatten.',
    'alpha_num' => ':Attribute mag alleen letters en cijfers bevatten.',
    'any_of' => ':Attribute is ongeldig.',
    'array' => ':Attribute moet een lijst zijn.',
    'array_keys' => ':Attribute mag alleen de volgende sleutels bevatten: :values.',
    'ascii' => ':Attribute mag alleen alfanumerieke tekens en symbolen van één byte bevatten.',
    'base64' => ':Attribute moet een geldige Base64-tekenreeks zijn.',
    'before' => ':Attribute moet een datum vóór :date zijn.',
    'before_or_equal' => ':Attribute moet een datum op of vóór :date zijn.',
    'between' => [
        'array' => ':Attribute moet tussen :min en :max items bevatten.',
        'file' => ':Attribute moet tussen :min en :max kilobytes zijn.',
        'numeric' => ':Attribute moet tussen :min en :max liggen.',
        'string' => ':Attribute moet tussen :min en :max tekens lang zijn.',
    ],
    'boolean' => ':Attribute moet waar of onwaar zijn.',
    'can' => ':Attribute bevat een waarde die niet is toegestaan.',
    'confirmed' => 'De bevestiging van :attribute komt niet overeen.',
    'contains' => 'Er ontbreekt een verplichte waarde in :attribute.',
    'current_password' => 'Het wachtwoord is onjuist.',
    'date' => ':Attribute moet een geldige datum zijn.',
    'date_equals' => ':Attribute moet een datum gelijk aan :date zijn.',
    'date_format' => ':Attribute moet overeenkomen met het formaat :format.',
    'decimal' => ':Attribute moet :decimal decimalen hebben.',
    'declined' => ':Attribute moet geweigerd zijn.',
    'declined_if' => ':Attribute moet geweigerd zijn als :other :value is.',
    'different' => ':Attribute en :other moeten verschillen.',
    'digits' => ':Attribute moet uit :digits cijfers bestaan.',
    'digits_between' => ':Attribute moet tussen :min en :max cijfers bevatten.',
    'dimensions' => ':Attribute heeft ongeldige afmetingen.',
    'distinct' => ':Attribute bevat een dubbele waarde.',
    'doesnt_contain' => ':Attribute mag geen van de volgende waarden bevatten: :values.',
    'doesnt_end_with' => ':Attribute mag niet eindigen op een van de volgende waarden: :values.',
    'doesnt_start_with' => ':Attribute mag niet beginnen met een van de volgende waarden: :values.',
    'email' => ':Attribute moet een geldig e-mailadres zijn.',
    'encoding' => ':Attribute moet in :encoding gecodeerd zijn.',
    'ends_with' => ':Attribute moet eindigen op een van de volgende waarden: :values.',
    'enum' => 'De gekozen :attribute is ongeldig.',
    'exists' => 'De gekozen :attribute is ongeldig.',
    'extensions' => ':Attribute moet een van de volgende extensies hebben: :values.',
    'file' => ':Attribute moet een bestand zijn.',
    'filled' => ':Attribute moet een waarde hebben.',
    'gt' => [
        'array' => ':Attribute moet meer dan :value items bevatten.',
        'file' => ':Attribute moet groter zijn dan :value kilobytes.',
        'numeric' => ':Attribute moet groter zijn dan :value.',
        'string' => ':Attribute moet langer zijn dan :value tekens.',
    ],
    'gte' => [
        'array' => ':Attribute moet :value of meer items bevatten.',
        'file' => ':Attribute moet groter dan of gelijk aan :value kilobytes zijn.',
        'numeric' => ':Attribute moet groter dan of gelijk aan :value zijn.',
        'string' => ':Attribute moet minstens :value tekens lang zijn.',
    ],
    'hex_color' => ':Attribute moet een geldige hexadecimale kleur zijn.',
    'image' => ':Attribute moet een afbeelding zijn.',
    'in' => 'De gekozen :attribute is ongeldig.',
    'in_array' => ':Attribute moet voorkomen in :other.',
    'in_array_keys' => ':Attribute moet minstens een van de volgende sleutels bevatten: :values.',
    'integer' => ':Attribute moet een geheel getal zijn.',
    'ip' => ':Attribute moet een geldig IP-adres zijn.',
    'ipv4' => ':Attribute moet een geldig IPv4-adres zijn.',
    'ipv6' => ':Attribute moet een geldig IPv6-adres zijn.',
    'json' => ':Attribute moet een geldige JSON-tekenreeks zijn.',
    'list' => ':Attribute moet een lijst zijn.',
    'lowercase' => ':Attribute mag alleen kleine letters bevatten.',
    'lt' => [
        'array' => ':Attribute moet minder dan :value items bevatten.',
        'file' => ':Attribute moet kleiner zijn dan :value kilobytes.',
        'numeric' => ':Attribute moet kleiner zijn dan :value.',
        'string' => ':Attribute moet korter zijn dan :value tekens.',
    ],
    'lte' => [
        'array' => ':Attribute mag niet meer dan :value items bevatten.',
        'file' => ':Attribute moet kleiner dan of gelijk aan :value kilobytes zijn.',
        'numeric' => ':Attribute moet kleiner dan of gelijk aan :value zijn.',
        'string' => ':Attribute mag hoogstens :value tekens lang zijn.',
    ],
    'mac_address' => ':Attribute moet een geldig MAC-adres zijn.',
    'max' => [
        'array' => ':Attribute mag niet meer dan :max items bevatten.',
        'file' => ':Attribute mag niet groter zijn dan :max kilobytes.',
        'numeric' => ':Attribute mag niet groter zijn dan :max.',
        'string' => ':Attribute mag niet langer zijn dan :max tekens.',
    ],
    'max_digits' => ':Attribute mag niet meer dan :max cijfers bevatten.',
    'mimes' => ':Attribute moet een bestand zijn van het type: :values.',
    'mimetypes' => ':Attribute moet een bestand zijn van het type: :values.',
    'min' => [
        'array' => ':Attribute moet minstens :min items bevatten.',
        'file' => ':Attribute moet minstens :min kilobytes zijn.',
        'numeric' => ':Attribute moet minstens :min zijn.',
        'string' => ':Attribute moet minstens :min tekens lang zijn.',
    ],
    'min_digits' => ':Attribute moet minstens :min cijfers bevatten.',
    'missing' => ':Attribute moet ontbreken.',
    'missing_if' => ':Attribute moet ontbreken als :other :value is.',
    'missing_unless' => ':Attribute moet ontbreken, tenzij :other :value is.',
    'missing_with' => ':Attribute moet ontbreken als :values aanwezig is.',
    'missing_with_all' => ':Attribute moet ontbreken als :values aanwezig zijn.',
    'multiple_of' => ':Attribute moet een veelvoud van :value zijn.',
    'not_in' => 'De gekozen :attribute is ongeldig.',
    'not_regex' => 'Het formaat van :attribute is ongeldig.',
    'numeric' => ':Attribute moet een getal zijn.',
    'password' => [
        'letters' => ':Attribute moet minstens één letter bevatten.',
        'mixed' => ':Attribute moet minstens één hoofdletter en één kleine letter bevatten.',
        'numbers' => ':Attribute moet minstens één cijfer bevatten.',
        'symbols' => ':Attribute moet minstens één symbool bevatten.',
        'uncompromised' => 'Dit :attribute komt voor in een datalek. Kies een ander :attribute.',
    ],
    'present' => ':Attribute moet aanwezig zijn.',
    'present_if' => ':Attribute moet aanwezig zijn als :other :value is.',
    'present_unless' => ':Attribute moet aanwezig zijn, tenzij :other :value is.',
    'present_with' => ':Attribute moet aanwezig zijn als :values aanwezig is.',
    'present_with_all' => ':Attribute moet aanwezig zijn als :values aanwezig zijn.',
    'prohibited' => ':Attribute is niet toegestaan.',
    'prohibited_if' => ':Attribute is niet toegestaan als :other :value is.',
    'prohibited_if_accepted' => ':Attribute is niet toegestaan als :other geaccepteerd is.',
    'prohibited_if_declined' => ':Attribute is niet toegestaan als :other geweigerd is.',
    'prohibited_unless' => ':Attribute is niet toegestaan, tenzij :other in :values staat.',
    'prohibits' => ':Attribute staat niet toe dat :other aanwezig is.',
    'regex' => 'Het formaat van :attribute is ongeldig.',
    'required' => ':Attribute is verplicht.',
    'required_array_keys' => ':Attribute moet waarden bevatten voor: :values.',
    'required_if' => ':Attribute is verplicht als :other :value is.',
    'required_if_accepted' => ':Attribute is verplicht als :other geaccepteerd is.',
    'required_if_declined' => ':Attribute is verplicht als :other geweigerd is.',
    'required_unless' => ':Attribute is verplicht, tenzij :other in :values staat.',
    'required_with' => ':Attribute is verplicht als :values aanwezig is.',
    'required_with_all' => ':Attribute is verplicht als :values aanwezig zijn.',
    'required_without' => ':Attribute is verplicht als :values niet aanwezig is.',
    'required_without_all' => ':Attribute is verplicht als geen van :values aanwezig is.',
    'same' => ':Attribute moet overeenkomen met :other.',
    'size' => [
        'array' => ':Attribute moet :size items bevatten.',
        'file' => ':Attribute moet :size kilobytes zijn.',
        'numeric' => ':Attribute moet :size zijn.',
        'string' => ':Attribute moet :size tekens lang zijn.',
    ],
    'starts_with' => ':Attribute moet beginnen met een van de volgende waarden: :values.',
    'string' => ':Attribute moet tekst zijn.',
    'timezone' => ':Attribute moet een geldige tijdzone zijn.',
    'unique' => ':Attribute is al in gebruik.',
    'uploaded' => 'Het uploaden van :attribute is mislukt.',
    'uppercase' => ':Attribute mag alleen hoofdletters bevatten.',
    'url' => ':Attribute moet een geldige URL zijn.',
    'ulid' => ':Attribute moet een geldige ULID zijn.',
    'uuid' => ':Attribute moet een geldige UUID zijn.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'code' => 'code',
        'current_password' => 'huidig wachtwoord',
        'description' => 'omschrijving',
        'email' => 'e-mailadres',
        'locale' => 'taal',
        'name' => 'naam',
        'password' => 'wachtwoord',
        'password_confirmation' => 'wachtwoordbevestiging',
        'permissions' => 'permissies',
        'recovery_code' => 'herstelcode',
        'role_ids' => 'rollen',
    ],

];
