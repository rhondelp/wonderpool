<?php

namespace Database\Factories\Concerns;

/**
 * Philippine-flavored fake data shared by factories (Faker has no en_PH person provider).
 */
trait PhilippineData
{
    /**
     * @var list<string>
     */
    protected static array $phFirstNames = [
        'Juan', 'Maria', 'Jose', 'Ana', 'Mark', 'Angelica', 'Paolo', 'Kristine', 'Rodel', 'Jasmine',
        'Carlo', 'Liza', 'Miguel', 'Camille', 'Ramon', 'Bea', 'Nico', 'Joy', 'Arnel', 'Precious',
    ];

    /**
     * @var list<string>
     */
    protected static array $phLastNames = [
        'Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Mendoza', 'Bautista', 'Villanueva', 'Ramos', 'Aquino', 'Castillo',
        'Fernandez', 'Navarro', 'Pascual', 'Soriano', 'Manalo', 'Salazar', 'Domingo', 'Tolentino', 'Gonzales', 'Lim',
    ];

    /**
     * Full Filipino name, e.g. "Maria Dela Cruz".
     */
    protected function phName(): string
    {
        return fake()->randomElement(static::$phFirstNames).' '.fake()->randomElement(static::$phLastNames);
    }

    /**
     * Philippine mobile number in E.164 format, e.g. "+639171234567".
     */
    protected function phMobile(): string
    {
        return '+639'.fake()->numerify('#########');
    }

    /**
     * Email derived from a name, on a reserved example domain.
     */
    protected function phEmail(string $name): string
    {
        $local = strtolower(preg_replace('/[^a-z]+/i', '.', $name) ?? 'guest');

        return trim($local, '.').fake()->unique()->numberBetween(1, 99999).'@example.com';
    }
}
