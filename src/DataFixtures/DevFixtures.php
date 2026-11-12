<?php

declare(strict_types=1);

/*
 * This file is part of the Dowsing Machine project.
 *
 * (c) Dowsing Machine contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Archetype;
use App\Entity\ArchetypeTranslation;
use App\Entity\Borrow;
use App\Entity\Channel;
use App\Entity\Deck;
use App\Entity\DeckCard;
use App\Entity\DeckVersion;
use App\Entity\Event;
use App\Entity\EventDeckEntry;
use App\Entity\EventDeckRegistration;
use App\Entity\EventEngagement;
use App\Entity\EventStaff;
use App\Entity\HomepageLayout;
use App\Entity\HomepageLayoutTranslation;
use App\Entity\MenuCategory;
use App\Entity\MenuCategoryTranslation;
use App\Entity\Notification;
use App\Entity\Page;
use App\Entity\PageTranslation;
use App\Entity\TcgdexSetMapping;
use App\Entity\User;
use App\Enum\BorrowStatus;
use App\Enum\DeckFormat;
use App\Enum\DeckStatus;
use App\Enum\EngagementState;
use App\Enum\EventVisibility;
use App\Enum\NotificationType;
use App\Enum\ParticipationMode;
use App\Enum\TournamentStructure;
use App\Service\DeckListParser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DevFixtures extends Fixture
{
    /**
     * Editorial content author for fixtures — a fictional content author.
     * Mirrors the production backfill (content attributed to its writer) so
     * dev shows real bylines (F19.8).
     */
    private ?User $contentAuthor = null;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly DeckListParser $deckListParser,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        [$appChannel, $contentChannel] = $this->createChannels($manager);

        $admin = $this->createAdmin($manager);
        $organizer = $this->createOrganizer($manager);
        $this->createTranslator($manager);
        $borrower = $this->createBorrower($manager);
        $staff1 = $this->createStaff1($manager);
        $staff2 = $this->createStaff2($manager);
        $lender = $this->createLender($manager);
        $this->contentAuthor = $this->createEditor($manager);
        $this->createUnverifiedUser($manager);
        $todayEvent = $this->createEventToday($manager, $admin, $borrower, $staff1);
        $futureEvent = $this->createEventInTwoMonths($manager, $admin, $lender, $staff1, $staff2);
        $this->createInvitationalEvent($manager, $organizer, $admin, $staff2);
        $this->createDraftEvent($manager, $organizer, $admin);

        // Create archetypes (descriptions are now stored as EN translations).
        // The optional first/last publishedAt args showcase the F2.27 freshness UI:
        // mix archetypes published months ago (some untouched, some recently
        // updated) with brand-new ones.
        $archetypeIronThorns = $this->createArchetype($manager, 'Iron Thorns ex', ['iron-thorns'], true, ['Aggressive', 'Spread'], 4, new \DateTimeImmutable('-6 months'), new \DateTimeImmutable('-3 weeks'));
        $this->addArchetypeTranslation($manager, $archetypeIronThorns, 'en', 'Iron Thorns ex', 'A powerful **Iron Thorns ex** deck built around the Paradox Pokemon. Uses heavy energy acceleration and spread damage to overwhelm opponents.');

        $archetypeAncientBox = $this->createArchetype($manager, 'Ancient Box', ['roaring-moon', 'flutter-mane'], true, ['Toolbox', 'Aggressive'], 18, new \DateTimeImmutable('-9 months'));
        $this->addArchetypeTranslation($manager, $archetypeAncientBox, 'en', 'Ancient Box', 'The **Ancient Box** archetype combines multiple Ancient Pokemon to leverage Ancient support cards for a versatile attack strategy.');
        $this->addArchetypeTranslation($manager, $archetypeAncientBox, 'fr', 'Box Anciens', "L'archétype **Box Anciens** combine plusieurs Pokémon Anciens pour tirer parti des cartes de support Anciennes dans une stratégie d'attaque polyvalente.");

        $regidragoDescription = <<<'MARKDOWN'
**Regidrago VSTAR** is generally considered to be the best deck in the format. It has all the options ever, and more. It can lock the opponent in multiple ways (Item lock, Ability lock, Special Energy lock...), decimate Basic boards with [[archetype:kyurem]], oneshot anything with [[archetype:salamence-ex]], and even take an extra turn with [[card:UPR-100]].

Although Regidrago has been a top tier deck for a long time, it has gained many options in the last year, such as [[card:SCR-133]] to deal with its main weakness (its lack of Energy acceleration), [[card:SFA-46]] for the mirror match and many other relevant decks, and [[card:PRE-4]] to buy time when going second.

It's gotten to the point where many lists are trying unconventional techs to try to get an edge in the mirror match.
MARKDOWN;
        $archetypeRegidrago = $this->createArchetype($manager, 'Regidrago', ['regidrago'], true, ['Combo', 'Lock', 'Toolbox'], 0, new \DateTimeImmutable('-1 year'), new \DateTimeImmutable('-5 days'));
        $this->addArchetypeTranslation($manager, $archetypeRegidrago, 'en', 'Regidrago', $regidragoDescription);
        $regidragoDescriptionFr = <<<'MARKDOWN'
**Regidrago VSTAR** est généralement considéré comme le meilleur deck du format. Il dispose de toutes les options possibles, et même plus. Il peut verrouiller l'adversaire de multiples façons (blocage d'Objets, blocage de Talents, blocage d'Énergies spéciales...), décimer les bancs de Bases avec [[archetype:kyurem]], mettre KO n'importe quoi avec [[archetype:salamence-ex]], et même prendre un tour supplémentaire avec [[card:UPR-100]].

Bien que Regidrago soit un deck de haut niveau depuis longtemps, il a gagné de nombreuses options cette dernière année, comme [[card:SCR-133]] pour pallier sa principale faiblesse (son manque d'accélération d'Énergie), [[card:SFA-46]] pour le match miroir et de nombreux autres decks pertinents, et [[card:PRE-4]] pour gagner du temps en passant second.

On en est au point où de nombreuses listes essaient des techs non conventionnelles pour tenter d'obtenir un avantage dans le match miroir.
MARKDOWN;
        $this->addArchetypeTranslation($manager, $archetypeRegidrago, 'fr', 'Regidrago', $regidragoDescriptionFr);

        $archetypeKyurem = $this->createArchetype($manager, 'Kyurem', ['kyurem'], true, ['Spread'], 37, new \DateTimeImmutable('-4 months'), new \DateTimeImmutable('-2 weeks'));
        $this->addArchetypeTranslation($manager, $archetypeKyurem, 'en', 'Kyurem', 'A **Kyurem** archetype that punishes Basic-heavy boards with its spread attack, often teched into [[archetype:regidrago]] builds.');

        $archetypeSalamence = $this->createArchetype($manager, 'Salamence ex', ['salamence'], true, ['Aggressive'], 36, new \DateTimeImmutable('-5 months'));
        $this->addArchetypeTranslation($manager, $archetypeSalamence, 'en', 'Salamence ex', 'The **Salamence ex** archetype leverages its massive damage output to oneshot virtually anything. Commonly used as a finisher in [[archetype:regidrago]] lists.');

        $archetypeLugia = $this->createArchetype($manager, 'Lugia Archeops', ['lugia', 'archeops'], false, [], 8);

        $archetypeCharizardEevee = $this->createArchetype($manager, 'Charizard Flareon', ['charizard', 'eevee'], true, ['Aggressive', 'Combo'], 19, new \DateTimeImmutable('-2 weeks'));
        $this->addArchetypeTranslation($manager, $archetypeCharizardEevee, 'en', 'Charizard Flareon', 'A **Charizard & Flareon** deck that evolves Eevee into Flareon for energy acceleration, fueling Charizard\'s massive fire attacks.');
        $this->addArchetypeTranslation($manager, $archetypeCharizardEevee, 'fr', 'Dracaufeu Pyroli', "Un deck **Dracaufeu & Pyroli** qui fait évoluer Évoli en Pyroli pour l'accélération d'énergie, alimentant les attaques de feu massives de Dracaufeu.");

        $ironThorns = $this->createDeck($manager, $admin, 'Iron Thorns');
        $ironThorns->setArchetype($archetypeIronThorns);
        $ironThorns->setLanguages(['en']);
        $ironThorns->setPublic(true);
        $this->createIronThornsDeckVersion($manager, $ironThorns);
        $this->createIronThornsDeckVersionTwo($manager, $ironThorns);
        $this->createIronThornsDeckVersionThree($manager, $ironThorns);

        $ancientBox = $this->createDeck($manager, $admin, 'Ancient Box');
        $ancientBox->setArchetype($archetypeAncientBox);
        $ancientBox->setLanguages(['en']);
        $this->createAncientBoxDeckVersion($manager, $ancientBox);

        $lenderDeck = $this->createDeck($manager, $lender, 'Regidrago');
        $lenderDeck->setArchetype($archetypeRegidrago);
        $lenderDeck->setLanguages(['en']);
        $lenderDeck->setPublic(true);
        $this->createRegidragoDeckVersion($manager, $lenderDeck);
        $this->createRegidragoDeckVersionTwo($manager, $lenderDeck);

        // Archetype variant decks (no owner)
        $this->createRegidragoVariants($manager, $archetypeRegidrago);

        $borrowerDeck = $this->createDeck($manager, $borrower, 'Lugia Archeops');
        $borrowerDeck->setArchetype($archetypeLugia);
        $borrowerDeck->setLanguages(['en']);
        $this->createLugiaArcheopsDeckVersion($manager, $borrowerDeck);

        $charizardFlareon = $this->createDeck($manager, $organizer, 'Charizard Flareon');
        $charizardFlareon->setArchetype($archetypeCharizardEevee);
        $charizardFlareon->setLanguages(['en', 'fr']);
        $charizardFlareon->setPublic(true);
        $this->createCharizardFlareonDeckVersion($manager, $charizardFlareon);

        $archetypeShadowRider = $this->createArchetype($manager, 'Shadow Rider Calyrex', ['calyrex-shadow-rider'], true, ['Control'], 3, new \DateTimeImmutable('-7 months'), new \DateTimeImmutable('-2 months'));
        $this->addArchetypeTranslation($manager, $archetypeShadowRider, 'en', 'Shadow Rider Calyrex', 'A **Shadow Rider Calyrex VMAX** control deck that uses hand disruption and item lock to starve the opponent of resources.');
        $shadowRider = $this->createDeck($manager, $admin, 'Shadow Rider Calyrex');
        $shadowRider->setArchetype($archetypeShadowRider);
        $shadowRider->setLanguages(['en', 'jp']);
        $shadowRider->setPublic(true);
        $this->createShadowRiderDeckVersion($manager, $shadowRider);

        // The rest of the production catalog, in production order (positions
        // match prod; the archetypes above fill the gaps). No tags or
        // descriptions: they only make the catalog and sitemap realistic.
        foreach ([
            1 => ['Mega Absol', ['absol-mega']],
            2 => ['Evo Vileplume', ['vileplume']],
            5 => ['Snorlax Stall', ['snorlax']],
            6 => ['Gardevoir', ['gardevoir']],
            7 => ['Ceruledge', ['ceruledge']],
            9 => ['Electro Rain OTK', []],
            10 => ['Mew VMAX', ['mew']],
            11 => ['Miraidon', ['miraidon']],
            12 => ['Dusknoir Spread', ['dusknoir']],
            13 => ['ADP', ['arceus', 'dialga', 'palkia']],
            14 => ['Meloetta OTK', ['meloetta']],
            15 => ['Tsareena', ['tsareena']],
            16 => ['Slowking', ['slowking']],
            17 => ['Palafin', ['palafin']],
            20 => ['Kangaskhan Tank', ['kangaskhan']],
            21 => ['Hide n Sneak', []],
            22 => ["Archie's Baxcalibur", ['baxcalibur']],
            23 => ['Jellicent', ['jellicent']],
            24 => ['Blissey', ['blissey']],
            25 => ['Articuno Lock', ['articuno']],
            26 => ['Tatsugiri', ['tatsugiri']],
            27 => ['Lost Box', []],
            28 => ['Trevnoir', ['trevenant', 'dusknoir']],
            29 => ['Zoroark Checkmate', ['zoroark']],
            30 => ['Dragapult', ['dragapult']],
            31 => ['United Wings', []],
            32 => ['Gourgeist', ['gourgeist']],
            33 => ['Welder Box', []],
            34 => ['Great Tusk', ['great-tusk']],
            35 => ['Eternatus', ['eternatus']],
        ] as $position => [$name, $pokemonSlugs]) {
            $this->createArchetype($manager, $name, $pokemonSlugs, true, [], $position, new \DateTimeImmutable(\sprintf('-%d weeks', 40 - $position)));
        }

        // Update Regidrago translations with a deck link now that decks exist
        $regidragoTranslationEn = $archetypeRegidrago->getTranslation('en');
        if ($regidragoTranslationEn instanceof ArchetypeTranslation) {
            $regidragoTranslationEn->setDescription(\sprintf(
                "%s\n\nCheck out a sample Regidrago build: [[deck:%s]].",
                (string) $regidragoTranslationEn->getDescription(),
                $lenderDeck->getShortTag(),
            ));
        }

        $regidragoTranslationFr = $archetypeRegidrago->getTranslation('fr');
        if ($regidragoTranslationFr instanceof ArchetypeTranslation && 'fr' === $regidragoTranslationFr->getLocale()) {
            $regidragoTranslationFr->setDescription(\sprintf(
                "%s\n\nDécouvrez un exemple de build Regidrago : [[deck:%s]].",
                (string) $regidragoTranslationFr->getDescription(),
                $lenderDeck->getShortTag(),
            ));
        }

        $manager->flush();

        $pendingBorrow = $this->createBorrowFixtures($manager, $todayEvent, $futureEvent, $borrower, $lender, $admin, $ironThorns, $ancientBox, $lenderDeck, $staff1);

        $this->createDeckRegistrations($manager, $todayEvent, $ironThorns, $ancientBox, $lenderDeck);
        $this->createFinishedEvent($manager, $admin, $borrower, $staff1, $ironThorns, $ancientBox, $lenderDeck);

        $manager->flush();

        $this->createAdminNotifications($manager, $admin, $borrower, $todayEvent, $pendingBorrow);
        $this->createCmsFixtures($manager, $contentChannel);
        $this->createHomepageFixtures($manager, $lenderDeck, $futureEvent, $appChannel);
        $this->createContentHomepageFixtures($manager, $contentChannel);
        $this->createTcgdexSetMappings($manager);

        $manager->flush();
    }

    private function createAdmin(ObjectManager $manager): User
    {
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setFirstName('Jean-Michel');
        $admin->setLastName('Admin');
        $admin->setScreenName('Admin');
        $admin->setPlayerId('007');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);
        $admin->setPreferredLocale('en');
        $admin->setTimezone('Europe/Paris');

        $manager->persist($admin);

        return $admin;
    }

    private function createOrganizer(ObjectManager $manager): User
    {
        $organizer = new User();
        $organizer->setEmail('organizer@example.com');
        $organizer->setFirstName('Bob');
        $organizer->setLastName('Martin');
        $organizer->setScreenName('Organizer');
        $organizer->setPlayerId('PKM-ORG-001');
        $organizer->setPassword($this->passwordHasher->hashPassword($organizer, 'password'));
        $organizer->setRoles(['ROLE_ORGANIZER']);
        $organizer->setIsVerified(true);
        $organizer->setPreferredLocale('en');
        $organizer->setTimezone('Europe/Paris');

        $manager->persist($organizer);

        return $organizer;
    }

    /**
     * @see docs/features.md F9.8 — Translation roles & access
     */
    private function createTranslator(ObjectManager $manager): User
    {
        $translator = new User();
        $translator->setEmail('translator@example.com');
        $translator->setFirstName('Claire');
        $translator->setLastName('Traductrice');
        $translator->setScreenName('Translator');
        $translator->setPlayerId('PKM-TRA-001');
        $translator->setPassword($this->passwordHasher->hashPassword($translator, 'password'));
        $translator->setRoles(['ROLE_TRANSLATION_EDITOR']);
        $translator->setTranslationLocales(['fr']);
        $translator->setIsVerified(true);
        $translator->setPreferredLocale('fr');
        $translator->setTimezone('Europe/Paris');

        $manager->persist($translator);

        return $translator;
    }

    private function createBorrower(ObjectManager $manager): User
    {
        $borrower = new User();
        $borrower->setEmail('borrower@example.com');
        $borrower->setFirstName('Alice');
        $borrower->setLastName('Dupont');
        $borrower->setScreenName('Borrower');
        $borrower->setPlayerId('201');
        $borrower->setPassword($this->passwordHasher->hashPassword($borrower, 'password'));
        $borrower->setIsVerified(true);
        $borrower->setPreferredLocale('en');
        $borrower->setTimezone('Europe/Paris');

        $manager->persist($borrower);

        return $borrower;
    }

    private function createStaff1(ObjectManager $manager): User
    {
        $user = new User();
        $user->setEmail('staff1@example.com');
        $user->setFirstName('Diana');
        $user->setLastName('Rousseau');
        $user->setScreenName('StaffOne');
        $user->setPlayerId('101');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setIsVerified(true);
        $user->setPreferredLocale('en');
        $user->setTimezone('Europe/Paris');

        $manager->persist($user);

        return $user;
    }

    private function createStaff2(ObjectManager $manager): User
    {
        $user = new User();
        $user->setEmail('staff2@example.com');
        $user->setFirstName('Ethan');
        $user->setLastName('Moreau');
        $user->setScreenName('StaffTwo');
        $user->setPlayerId('102');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setIsVerified(true);
        $user->setPreferredLocale('en');
        $user->setTimezone('Europe/Paris');

        $manager->persist($user);

        return $user;
    }

    private function createLender(ObjectManager $manager): User
    {
        $user = new User();
        $user->setEmail('lender@example.com');
        $user->setFirstName('Fiona');
        $user->setLastName('Leclerc');
        $user->setScreenName('Lender');
        $user->setPlayerId('301');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setIsVerified(true);
        $user->setPreferredLocale('en');
        $user->setTimezone('Europe/Paris');

        $manager->persist($user);

        return $user;
    }

    private function createEditor(ObjectManager $manager): User
    {
        $user = new User();
        $user->setEmail('editor@example.com');
        $user->setFirstName('Gwen');
        $user->setLastName('Editeur');
        $user->setScreenName('AceTrainer');
        $user->setPlayerId('401');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_CMS_EDITOR']);
        $user->setIsVerified(true);
        $user->setPreferredLocale('en');
        $user->setTimezone('Europe/Paris');
        // Public author profile (F19.8) so dev shows rich bylines / JSON-LD.
        $user->setIsPublicAuthor(true);
        $user->setCredential('Expanded format specialist');
        $user->setBio('Tournament grinder and Expanded format enthusiast. Curates and maintains the archetype library, writing the matchup notes and metagame breakdowns behind each deck.');
        $user->setSameAs(['https://github.com/jbourdin']);
        $user->setPrimaryUrl('https://github.com/jbourdin');
        $user->setPublicSlug('editor');

        $manager->persist($user);

        return $user;
    }

    private function createUnverifiedUser(ObjectManager $manager): User
    {
        $user = new User();
        $user->setEmail('unverified@example.com');
        $user->setFirstName('Charlie');
        $user->setLastName('Pending');
        $user->setScreenName('Unverified');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setIsVerified(false);
        $user->setVerificationToken('test-verification-token');
        $user->setTokenExpiresAt(new \DateTimeImmutable('+1 day', new \DateTimeZone('UTC')));
        $user->setPreferredLocale('en');
        $user->setTimezone('UTC');

        $manager->persist($user);

        return $user;
    }

    private function createEventToday(ObjectManager $manager, User $organizer, User $borrower, User $staff1): Event
    {
        $event = new Event();
        $event->setName('Expanded Weekly #42');
        $event->setDate(new \DateTimeImmutable('today'));
        $event->setTimezone('Europe/Paris');
        $event->setLocation('12 Rue de la République, 69001 Lyon, France');
        $event->setOrganizer($organizer);
        $event->setRegistrationLink('https://pokemon-lyon.example.com/events/42');
        $event->setTournamentStructure(TournamentStructure::Swiss);
        $event->setFormat('Expanded');
        $event->setAllowCustody(true);

        $manager->persist($event);

        $engagement = new EventEngagement();
        $engagement->setEvent($event);
        $engagement->setUser($organizer);
        $engagement->setState(EngagementState::RegisteredPlaying);
        $engagement->setParticipationMode(ParticipationMode::Playing);
        $manager->persist($engagement);

        $borrowerEngagement = new EventEngagement();
        $borrowerEngagement->setEvent($event);
        $borrowerEngagement->setUser($borrower);
        $borrowerEngagement->setState(EngagementState::RegisteredPlaying);
        $borrowerEngagement->setParticipationMode(ParticipationMode::Playing);
        $manager->persist($borrowerEngagement);

        $staff = new EventStaff();
        $staff->setEvent($event);
        $staff->setUser($borrower);
        $staff->setAssignedBy($organizer);
        $manager->persist($staff);

        $staff1Engagement = new EventEngagement();
        $staff1Engagement->setEvent($event);
        $staff1Engagement->setUser($staff1);
        $staff1Engagement->setState(EngagementState::RegisteredPlaying);
        $staff1Engagement->setParticipationMode(ParticipationMode::Playing);
        $manager->persist($staff1Engagement);

        $staff1Assignment = new EventStaff();
        $staff1Assignment->setEvent($event);
        $staff1Assignment->setUser($staff1);
        $staff1Assignment->setAssignedBy($organizer);
        $manager->persist($staff1Assignment);

        return $event;
    }

    private function createEventInTwoMonths(ObjectManager $manager, User $organizer, User $lender, User $staff1, User $staff2): Event
    {
        $event = new Event();
        $event->setName('Lyon Expanded Cup 2026');
        $event->setDate(new \DateTimeImmutable('+2 months', new \DateTimeZone('Europe/Paris')));
        $event->setTimezone('Europe/Paris');
        $event->setLocation('12 Rue de la République, 69001 Lyon, France');
        $event->setOrganizer($organizer);
        $event->setRegistrationLink('https://pokemon-lyon.example.com/events/cup-2026');
        $event->setTournamentStructure(TournamentStructure::SwissTopCut);
        $event->setFormat('Expanded');

        $manager->persist($event);

        $engagement = new EventEngagement();
        $engagement->setEvent($event);
        $engagement->setUser($organizer);
        $engagement->setState(EngagementState::RegisteredSpectating);
        $engagement->setParticipationMode(ParticipationMode::Spectating);
        $manager->persist($engagement);

        $lenderEngagement = new EventEngagement();
        $lenderEngagement->setEvent($event);
        $lenderEngagement->setUser($lender);
        $lenderEngagement->setState(EngagementState::RegisteredPlaying);
        $lenderEngagement->setParticipationMode(ParticipationMode::Playing);
        $manager->persist($lenderEngagement);

        $staff1Assignment = new EventStaff();
        $staff1Assignment->setEvent($event);
        $staff1Assignment->setUser($staff1);
        $staff1Assignment->setAssignedBy($organizer);
        $manager->persist($staff1Assignment);

        $staff2Assignment = new EventStaff();
        $staff2Assignment->setEvent($event);
        $staff2Assignment->setUser($staff2);
        $staff2Assignment->setAssignedBy($organizer);
        $manager->persist($staff2Assignment);

        return $event;
    }

    /**
     * @see docs/features.md F3.13 — Player engagement states
     */
    private function createInvitationalEvent(ObjectManager $manager, User $organizer, User $admin, User $staff2): Event
    {
        $event = new Event();
        $event->setName('Invitation-Only Expanded Meetup');
        $event->setDate(new \DateTimeImmutable('+3 weeks', new \DateTimeZone('Europe/Paris')));
        $event->setTimezone('Europe/Paris');
        $event->setLocation('Private Game Room, Paris');
        $event->setOrganizer($organizer);
        $event->setRegistrationLink('https://pokemon-paris.example.com/meetup');
        $event->setTournamentStructure(TournamentStructure::Swiss);
        $event->setFormat('Expanded');
        $event->setIsInvitationOnly(true);

        $manager->persist($event);

        // Organizer registers as player
        $organizerEngagement = new EventEngagement();
        $organizerEngagement->setEvent($event);
        $organizerEngagement->setUser($organizer);
        $organizerEngagement->setState(EngagementState::RegisteredPlaying);
        $organizerEngagement->setParticipationMode(ParticipationMode::Playing);
        $manager->persist($organizerEngagement);

        // Admin is invited
        $adminEngagement = new EventEngagement();
        $adminEngagement->setEvent($event);
        $adminEngagement->setUser($admin);
        $adminEngagement->setState(EngagementState::Invited);
        $adminEngagement->setInvitedBy($organizer);
        $manager->persist($adminEngagement);

        // Staff2 assigned as staff
        $staffAssignment = new EventStaff();
        $staffAssignment->setEvent($event);
        $staffAssignment->setUser($staff2);
        $staffAssignment->setAssignedBy($organizer);
        $manager->persist($staffAssignment);

        return $event;
    }

    /**
     * @see docs/features.md F3.11 — Event visibility
     */
    private function createDraftEvent(ObjectManager $manager, User $organizer, User $admin): Event
    {
        $event = new Event();
        $event->setName('Draft Event — Not Yet Published');
        $event->setDate(new \DateTimeImmutable('+5 weeks', new \DateTimeZone('Europe/Paris')));
        $event->setTimezone('Europe/Paris');
        $event->setLocation('TBD');
        $event->setOrganizer($organizer);
        $event->setRegistrationLink('https://pokemon-paris.example.com/draft');
        $event->setFormat('Expanded');
        $event->setVisibility(EventVisibility::Draft);

        $manager->persist($event);

        // Admin is invited so they can see it
        $adminEngagement = new EventEngagement();
        $adminEngagement->setEvent($event);
        $adminEngagement->setUser($admin);
        $adminEngagement->setState(EngagementState::Invited);
        $adminEngagement->setInvitedBy($organizer);
        $manager->persist($adminEngagement);

        return $event;
    }

    /**
     * @see docs/features.md F3.17 — Tournament Results
     */
    private function createFinishedEvent(ObjectManager $manager, User $admin, User $borrower, User $staff1, Deck $ironThorns, Deck $ancientBox, Deck $regidrago): void
    {
        $event = new Event();
        $event->setName('Past Expanded Weekly #40');
        $event->setDate(new \DateTimeImmutable('-2 weeks', new \DateTimeZone('Europe/Paris')));
        $event->setTimezone('Europe/Paris');
        $event->setLocation('12 Rue de la République, 69001 Lyon, France');
        $event->setOrganizer($admin);
        $event->setRegistrationLink('https://pokemon-lyon.example.com/events/40');
        $event->setTournamentStructure(TournamentStructure::Swiss);
        $event->setFormat('Expanded');
        $event->setFinishedAt(new \DateTimeImmutable('-2 weeks +6 hours', new \DateTimeZone('Europe/Paris')));

        $manager->persist($event);

        // Engagements
        $adminEngagement = new EventEngagement();
        $adminEngagement->setEvent($event);
        $adminEngagement->setUser($admin);
        $adminEngagement->setState(EngagementState::RegisteredPlaying);
        $adminEngagement->setParticipationMode(ParticipationMode::Playing);
        $manager->persist($adminEngagement);

        $borrowerEngagement = new EventEngagement();
        $borrowerEngagement->setEvent($event);
        $borrowerEngagement->setUser($borrower);
        $borrowerEngagement->setState(EngagementState::RegisteredPlaying);
        $borrowerEngagement->setParticipationMode(ParticipationMode::Playing);
        $manager->persist($borrowerEngagement);

        $staff1Engagement = new EventEngagement();
        $staff1Engagement->setEvent($event);
        $staff1Engagement->setUser($staff1);
        $staff1Engagement->setState(EngagementState::RegisteredPlaying);
        $staff1Engagement->setParticipationMode(ParticipationMode::Playing);
        $manager->persist($staff1Engagement);

        $staffAssignment = new EventStaff();
        $staffAssignment->setEvent($event);
        $staffAssignment->setUser($staff1);
        $staffAssignment->setAssignedBy($admin);
        $manager->persist($staffAssignment);

        // Deck entries with results
        $ironThornsVersion = $ironThorns->getCurrentVersion();
        \assert(null !== $ironThornsVersion);
        $ancientBoxVersion = $ancientBox->getCurrentVersion();
        \assert(null !== $ancientBoxVersion);
        $regidragoVersion = $regidrago->getCurrentVersion();
        \assert(null !== $regidragoVersion);

        $entry1 = new EventDeckEntry();
        $entry1->setEvent($event);
        $entry1->setPlayer($admin);
        $entry1->setDeckVersion($ironThornsVersion);
        $entry1->setFinalPlacement(1);
        $entry1->setMatchRecord('3-0-0');
        $manager->persist($entry1);

        $entry2 = new EventDeckEntry();
        $entry2->setEvent($event);
        $entry2->setPlayer($borrower);
        $entry2->setDeckVersion($ancientBoxVersion);
        $entry2->setFinalPlacement(2);
        $entry2->setMatchRecord('2-1-0');
        $manager->persist($entry2);

        $entry3 = new EventDeckEntry();
        $entry3->setEvent($event);
        $entry3->setPlayer($staff1);
        $entry3->setDeckVersion($regidragoVersion);
        $entry3->setFinalPlacement(3);
        $entry3->setMatchRecord('1-2-0');
        $manager->persist($entry3);
    }

    /**
     * @param list<string> $pokemonSlugs
     * @param list<string> $playstyleTags
     */
    private function createArchetype(ObjectManager $manager, string $name, array $pokemonSlugs = [], bool $isPublished = false, array $playstyleTags = [], int $position = 0, ?\DateTimeImmutable $firstPublishedAt = null, ?\DateTimeImmutable $lastPublishedAt = null): Archetype
    {
        $archetype = new Archetype();
        $archetype->setName($name);
        $archetype->setPokemonSlugs($pokemonSlugs);
        $archetype->setIsPublished($isPublished);
        $archetype->setPosition($position);
        $archetype->setPlaystyleTags(array_map(
            static fn (string $tag): string => mb_convert_case(trim($tag), \MB_CASE_TITLE),
            $playstyleTags,
        ));

        if ($isPublished && $firstPublishedAt instanceof \DateTimeImmutable) {
            $this->backdatePublication($archetype, $firstPublishedAt, $lastPublishedAt);
        }

        $archetype->setAuthor($this->contentAuthor);

        $manager->persist($archetype);

        return $archetype;
    }

    /**
     * Reflectively pre-seed the publication timestamps on a fixture entity so
     * the Doctrine PrePersist hook (which null-coalesces both fields) keeps
     * them. Lets fixtures showcase the freshness UI with realistic spread —
     * "published last year, never touched" vs. "republished last week".
     *
     * @see docs/features.md F11.4 — CMS publication dates
     * @see docs/features.md F2.27 — Archetype publication dates
     */
    private function backdatePublication(object $entity, \DateTimeImmutable $firstPublishedAt, ?\DateTimeImmutable $lastPublishedAt = null): void
    {
        $reflection = new \ReflectionClass($entity);
        $reflection->getProperty('firstPublishedAt')->setValue($entity, $firstPublishedAt);
        $reflection->getProperty('lastPublishedAt')->setValue($entity, $lastPublishedAt ?? $firstPublishedAt);
    }

    /**
     * @see docs/features.md F9.6 — Archetype localization
     */
    private function addArchetypeTranslation(ObjectManager $manager, Archetype $archetype, string $locale, string $name, ?string $description = null): void
    {
        $translation = new ArchetypeTranslation();
        $translation->setArchetype($archetype);
        $translation->setLocale($locale);
        $translation->setName($name);
        $translation->setDescription($description);
        $archetype->addTranslation($translation);

        $manager->persist($translation);
    }

    /**
     * @see docs/features.md F18.13 — Archetype variant decks
     */
    private function createRegidragoVariants(ObjectManager $manager, Archetype $archetype): void
    {
        $rawList = <<<'LIST'
Pokémon: 19
4 Regidrago V SIT 135
3 Regidrago VSTAR SIT 136
1 Budew PRE 4
1 Crobat V DAA 104
1 Dedenne-GX UNB 57
1 Dialga-GX UPR 100
1 Dragapult ex TWM 130
1 Giratina VSTAR LOR 131
1 Kyurem SFA 47
1 Latias ex SSP 76
1 Noivern ex PAL 153
1 Noivern-GX BUS 99
1 Salamence ex JTG 114
1 Tapu Lele-GX GRI 60

Trainer: 31
4 Mysterious Treasure FLI 113
4 Professor's Research JTG 155
4 Quick Ball FST 237
3 VS Seeker PHF 109
2 Battle Compressor PHF 92
1 Crispin SCR 133
1 Faba LOT 173
1 Float Stone PLF 99
1 Guzma BUS 115
1 Hisuian Heavy Ball ASR 146
1 Iono PAL 185
1 Marnie SSH 169
1 Megaton Blower SSP 182
1 Muscle Band XY 121
1 N FCO 105
1 Parallel City BKT 145
1 Path to the Peak CRE 148
1 Raihan EVS 152
1 Super Rod PAL 188

Energy: 10
4 Double Dragon Energy ROS 97
4 Grass Energy SVE 1
2 Fire Energy SVE 2

Total Cards: 60
LIST;

        // Canonical variant: Regidrago
        $canonical = new Deck();
        $canonical->setName('Regidrago');
        $canonical->setArchetype($archetype);
        $canonical->setFormat(DeckFormat::Expanded);
        $canonical->setCanonical(true);
        $canonical->setPosition(0);
        $canonical->setPokemonSlugs(['dialga']);
        $canonical->setNotes(<<<'MARKDOWN'
## Strategy

Regidrago VSTAR leverages its **Apex Dragon** VSTAR Power to copy any attack from a Dragon-type Pokemon in the discard pile. The core engine uses [[card:SIT-136]] as the sole attacker, discarding Dragon-type Pokemon with powerful attacks early — then copying them for a single energy cost via **Double Turbo Energy**.

## Key Combos

- **Giratina VSTAR** in discard → copy **Lost Impact** (280 damage) for just one Double Turbo
- **Dragonite V** in discard → copy **Dragon Gale** for spread damage + snipe
- **Kyurem VMAX** in discard → copy **Max Frost** for scaling damage based on energy

## Setup

Turn 1 priority: use **Battle VIP Pass** and **Nest Ball** to bench two Regidrago V. **Battle Compressor** and **Ultra Ball** discard Dragon attackers early. **Professor Juniper** fuels the discard while refreshing your hand.

## Matchup Notes

Strong against single-prize decks thanks to VSTAR bulk (270 HP) and one-shot potential. Weak to **Path to the Peak** (shuts off VSTAR Power) — run 2–3 **Chaotic Swell** or **Field Blower** to counter.
MARKDOWN);
        $manager->persist($canonical);
        $this->createParsedDeckVersion($manager, $canonical, $rawList);

        // Alternate variant: Regidrago / Cinccino
        $alternate = new Deck();
        $alternate->setName('Alternate Regidrago');
        $alternate->setArchetype($archetype);
        $alternate->setFormat(DeckFormat::Expanded);
        $alternate->setPosition(1);
        $alternate->setPokemonSlugs(['dragonite']);
        $alternate->setNotes(<<<'MARKDOWN'
## Strategy

The **Regidrago / Cinccino** variant trades raw power for consistency. Instead of relying solely on [[card:SIT-136]], this build pairs it with a **Cinccino** draw engine — **Make Do** lets you discard a card and draw two, simultaneously fueling the discard pile with Dragon attackers and keeping your hand stocked.

## Key Differences from Canonical

- **Cinccino line** (Minccino + Cinccino) replaces heavy Supporter reliance — less dependent on turn 1 Professor Juniper
- Thinner Dragon target lineup (3–4 instead of 5–6) to make room for the draw engine
- More resilient to **N** and **Marnie** hand disruption thanks to bench-based draw

## Setup

Turn 1: bench Regidrago V + Minccino. Turn 2: evolve both — Cinccino starts drawing while Regidrago VSTAR charges. Use **Level Ball** to search Minccino and Cinccino (both under 90 HP).

## Weaknesses

Bigger bench means more liability against **Sky Seal Stone** + Boss's Orders snipes. The Cinccino line gives up easy two-prize KOs if dragged active.
MARKDOWN);
        $manager->persist($alternate);
        $this->createParsedDeckVersion($manager, $alternate, $rawList);

        // Third variant: minimal placeholder
        $third = new Deck();
        $third->setName('Third Regidrago');
        $third->setArchetype($archetype);
        $third->setFormat(DeckFormat::Expanded);
        $third->setPosition(2);
        $third->setPokemonSlugs(['dragapult']);
        $third->setNotes('A lightweight Regidrago build focused on speed.');
        $manager->persist($third);

        foreach ([$canonical, $alternate, $third] as $variant) {
            $variant->setAuthor($this->contentAuthor);
        }
    }

    /**
     * Parse a raw decklist and create a DeckVersion with DeckCard entities.
     */
    private function createParsedDeckVersion(ObjectManager $manager, Deck $deck, string $rawList): void
    {
        $result = $this->deckListParser->parse($rawList);

        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(1);
        $version->setRawList($rawList);

        foreach ($result->cards as $parsedCard) {
            $card = new DeckCard();
            $card->setCardName($parsedCard->cardName);
            $card->setSetCode($parsedCard->setCode);
            $card->setCardNumber($parsedCard->cardNumber);
            $card->setQuantity($parsedCard->quantity);
            $card->setCardType($parsedCard->cardType);
            $version->addCard($card);
        }

        $manager->persist($version);
        $deck->setCurrentVersion($version);
    }

    private function createDeck(ObjectManager $manager, User $owner, string $name, DeckStatus $status = DeckStatus::Available): Deck
    {
        $deck = new Deck();
        $deck->setName($name);
        $deck->setOwner($owner);
        $deck->setFormat(DeckFormat::Expanded);
        $deck->setStatus($status);

        $manager->persist($deck);

        return $deck;
    }

    private function createIronThornsDeckVersion(ObjectManager $manager, Deck $deck): void
    {
        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(1);
        $version->setRawList($this->getIronThornsRawDeckList());

        foreach ($this->getIronThornsDeckCards() as $cardData) {
            $card = new DeckCard();
            $card->setCardName($cardData['name']);
            $card->setSetCode($cardData['set']);
            $card->setCardNumber($cardData['number']);
            $card->setQuantity($cardData['quantity']);
            $card->setCardType($cardData['type']);

            $version->addCard($card);
        }

        $manager->persist($version);

        $deck->setCurrentVersion($version);
    }

    /**
     * @return list<array{name: string, set: string, number: string, quantity: int, type: string, subtype: string|null}>
     */
    private function getIronThornsDeckCards(): array
    {
        return [
            // Pokemon (4)
            ['name' => 'Iron Thorns ex', 'set' => 'TWM', 'number' => '77', 'quantity' => 3, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Iron Thorns ex', 'set' => 'PRE', 'number' => '32', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],

            // Trainer — Supporter (25)
            ['name' => 'Plumeria', 'set' => 'BUS', 'number' => '120', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Eri', 'set' => 'TEF', 'number' => '146', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Sidney', 'set' => 'FST', 'number' => '241', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Guzma & Hala', 'set' => 'CEC', 'number' => '193', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Guzma', 'set' => 'BUS', 'number' => '115', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Lusamine', 'set' => 'CIN', 'number' => '96', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Team Flare Grunt', 'set' => 'GEN', 'number' => '73', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Cynthia & Caitlin', 'set' => 'CEC', 'number' => '189', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Cynthia', 'set' => 'UPR', 'number' => '119', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Faba', 'set' => 'LOT', 'number' => '173', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'N', 'set' => 'NVI', 'number' => '92', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Bellelba & Brycen-Man', 'set' => 'CEC', 'number' => '186', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Penny', 'set' => 'SVI', 'number' => '183', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],

            // Trainer — Item (16)
            ['name' => "Trainers' Mail", 'set' => 'ROS', 'number' => '92', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'VS Seeker', 'set' => 'PHF', 'number' => '109', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Tag Call', 'set' => 'CEC', 'number' => '206', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Enhanced Hammer', 'set' => 'TWM', 'number' => '148', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Heavy Ball', 'set' => 'BKT', 'number' => '140', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Nest Ball', 'set' => 'PAF', 'number' => '84', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Megaton Blower', 'set' => 'SSP', 'number' => '182', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],

            // Trainer — Tool (3)
            ['name' => 'Future Booster Energy Capsule', 'set' => 'TEF', 'number' => '149', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'tool'],
            ['name' => 'Stealthy Hood', 'set' => 'UNB', 'number' => '186', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],
            ['name' => 'Tool Jammer', 'set' => 'BST', 'number' => '136', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],

            // Trainer — Stadium (3)
            ['name' => 'Chaotic Swell', 'set' => 'CEC', 'number' => '187', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'stadium'],
            ['name' => 'Jubilife Village', 'set' => 'ASR', 'number' => '148', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'stadium'],
            ['name' => 'Thunder Mountain Prism Star', 'set' => 'LOT', 'number' => '191', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'stadium'],

            // Energy (9)
            ['name' => 'Speed Lightning Energy', 'set' => 'RCL', 'number' => '173', 'quantity' => 4, 'type' => 'energy', 'subtype' => null],
            ['name' => 'Double Colorless Energy', 'set' => 'SLG', 'number' => '69', 'quantity' => 4, 'type' => 'energy', 'subtype' => null],
            ['name' => 'Capture Energy', 'set' => 'RCL', 'number' => '171', 'quantity' => 1, 'type' => 'energy', 'subtype' => null],
        ];
    }

    private function getIronThornsRawDeckList(): string
    {
        return <<<'PTCG'
            Pokémon: 4
            3 Iron Thorns ex TWM 77
            1 Iron Thorns ex PRE 32

            Trainer: 47
            4 Plumeria BUS 120
            2 Eri TEF 146
            2 Sidney FST 241
            2 Guzma & Hala CEC 193
            2 Guzma BUS 115
            2 Lusamine CIN 96
            2 Team Flare Grunt GEN 73
            1 Cynthia & Caitlin CEC 189
            1 Cynthia UPR 119
            1 Faba LOT 173
            1 N NVI 92
            1 Bellelba & Brycen-Man CEC 186
            1 Penny SVI 183
            4 Trainers' Mail ROS 92
            3 VS Seeker PHF 109
            2 Tag Call CEC 206
            2 Enhanced Hammer TWM 148
            2 Heavy Ball BKT 140
            2 Nest Ball PAF 84
            1 Megaton Blower SSP 182
            2 Future Booster Energy Capsule TEF 149
            1 Stealthy Hood UNB 186
            1 Tool Jammer BST 136
            3 Chaotic Swell CEC 187
            1 Jubilife Village ASR 148
            1 Thunder Mountain Prism Star LOT 191

            Energy: 9
            4 Speed Lightning Energy RCL 173
            4 Double Colorless Energy SLG 69
            1 Capture Energy RCL 171

            Total Cards: 60
            PTCG;
    }

    /**
     * @see docs/features.md F2.9 — Deck version history
     */
    private function createIronThornsDeckVersionTwo(ObjectManager $manager, Deck $deck): void
    {
        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(2);

        // V2 changes vs V1: removed Megaton Blower SSP 182, added Crushing Hammer,
        // changed Plumeria quantity 4->3, changed Enhanced Hammer quantity 2->3
        $cardsVersion2 = $this->getIronThornsDeckCards();

        // Remove Megaton Blower
        $cardsVersion2 = array_filter($cardsVersion2, static fn (array $card): bool => 'Megaton Blower' !== $card['name']);
        $cardsVersion2 = array_values($cardsVersion2);

        // Add Crushing Hammer
        $cardsVersion2[] = ['name' => 'Crushing Hammer', 'set' => 'SSH', 'number' => '159', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'];

        $this->applyQuantityOverrides($cardsVersion2, ['Plumeria' => 3, 'Enhanced Hammer' => 3]);
        $version->setRawList($this->buildRawList($cardsVersion2));

        foreach ($cardsVersion2 as $cardData) {
            $card = new DeckCard();
            $card->setCardName($cardData['name']);
            $card->setSetCode($cardData['set']);
            $card->setCardNumber($cardData['number']);
            $card->setQuantity($cardData['quantity']);
            $card->setCardType($cardData['type']);

            $version->addCard($card);
        }

        $manager->persist($version);

        $deck->setCurrentVersion($version);
    }

    /**
     * @see docs/features.md F2.9 — Deck version history
     */
    private function createIronThornsDeckVersionThree(ObjectManager $manager, Deck $deck): void
    {
        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(3);

        // V3 changes vs V2: removed Stealthy Hood, removed Capture Energy,
        // added 2 Lightning Energy, changed Chaotic Swell 3->2, changed VS Seeker 3->4
        $cardsVersion3 = $this->getIronThornsDeckCards();

        // Remove Megaton Blower (same as v2), Stealthy Hood, Capture Energy
        $cardsVersion3 = array_filter(
            $cardsVersion3,
            static fn (array $card): bool => !\in_array($card['name'], ['Megaton Blower', 'Stealthy Hood', 'Capture Energy'], true),
        );
        $cardsVersion3 = array_values($cardsVersion3);

        // Add Crushing Hammer (same as v2)
        $cardsVersion3[] = ['name' => 'Crushing Hammer', 'set' => 'SSH', 'number' => '159', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'];
        // Add Lightning Energy
        $cardsVersion3[] = ['name' => 'Lightning Energy', 'set' => 'SVE', 'number' => '4', 'quantity' => 2, 'type' => 'energy', 'subtype' => null];

        $this->applyQuantityOverrides($cardsVersion3, ['Plumeria' => 3, 'Enhanced Hammer' => 3, 'Chaotic Swell' => 2, 'VS Seeker' => 4]);
        $version->setRawList($this->buildRawList($cardsVersion3));

        foreach ($cardsVersion3 as $cardData) {
            $card = new DeckCard();
            $card->setCardName($cardData['name']);
            $card->setSetCode($cardData['set']);
            $card->setCardNumber($cardData['number']);
            $card->setQuantity($cardData['quantity']);
            $card->setCardType($cardData['type']);

            $version->addCard($card);
        }

        $manager->persist($version);

        $deck->setCurrentVersion($version);
    }

    /**
     * @see docs/features.md F2.9 — Deck version history
     */
    private function createRegidragoDeckVersionTwo(ObjectManager $manager, Deck $deck): void
    {
        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(2);

        // V2 changes vs V1: removed Wobbuffet, removed Leafy Camo Poncho,
        // added Giratina VSTAR, added Float Stone,
        // changed Guzma 2->1, changed Professor's Research 3->4
        $cardsVersion2 = $this->getRegidragoDeckCards();

        $cardsVersion2 = array_filter(
            $cardsVersion2,
            static fn (array $card): bool => !\in_array($card['name'], ['Wobbuffet', 'Leafy Camo Poncho'], true),
        );
        $cardsVersion2 = array_values($cardsVersion2);

        $cardsVersion2[] = ['name' => 'Giratina VSTAR', 'set' => 'LOR', 'number' => '131', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null];
        $cardsVersion2[] = ['name' => 'Float Stone', 'set' => 'PLF', 'number' => '99', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'];

        $this->applyQuantityOverrides($cardsVersion2, ['Guzma' => 1, "Professor's Research" => 4]);
        $version->setRawList($this->buildRawList($cardsVersion2));

        foreach ($cardsVersion2 as $cardData) {
            $card = new DeckCard();
            $card->setCardName($cardData['name']);
            $card->setSetCode($cardData['set']);
            $card->setCardNumber($cardData['number']);
            $card->setQuantity($cardData['quantity']);
            $card->setCardType($cardData['type']);

            $version->addCard($card);
        }

        $manager->persist($version);

        $deck->setCurrentVersion($version);
    }

    private function createAncientBoxDeckVersion(ObjectManager $manager, Deck $deck): void
    {
        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(1);
        $version->setRawList($this->getAncientBoxRawDeckList());

        foreach ($this->getAncientBoxDeckCards() as $cardData) {
            $card = new DeckCard();
            $card->setCardName($cardData['name']);
            $card->setSetCode($cardData['set']);
            $card->setCardNumber($cardData['number']);
            $card->setQuantity($cardData['quantity']);
            $card->setCardType($cardData['type']);

            $version->addCard($card);
        }

        $manager->persist($version);

        $deck->setCurrentVersion($version);
    }

    /**
     * @return list<array{name: string, set: string, number: string, quantity: int, type: string, subtype: string|null}>
     */
    private function getAncientBoxDeckCards(): array
    {
        return [
            // Pokemon (13)
            ['name' => 'Flutter Mane', 'set' => 'TEF', 'number' => '78', 'quantity' => 4, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Roaring Moon', 'set' => 'TEF', 'number' => '109', 'quantity' => 4, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Roaring Moon ex', 'set' => 'PR-SV', 'number' => '67', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Great Tusk', 'set' => 'TEF', 'number' => '97', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Koraidon', 'set' => 'SSP', 'number' => '116', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Munkidori', 'set' => 'TWM', 'number' => '95', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Pecharunt ex', 'set' => 'SFA', 'number' => '85', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],

            // Trainer — Supporter (16)
            ['name' => "Professor Sada's Vitality", 'set' => 'PAR', 'number' => '170', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => "Explorer's Guidance", 'set' => 'TEF', 'number' => '147', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => "Boss's Orders", 'set' => 'PAL', 'number' => '172', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Surfer', 'set' => 'SSP', 'number' => '187', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => "Janine's Secret Art", 'set' => 'PRE', 'number' => '112', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => "Professor's Research", 'set' => 'PAF', 'number' => '87', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],

            // Trainer — Item (17)
            ['name' => 'Earthen Vessel', 'set' => 'PAR', 'number' => '163', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Nest Ball', 'set' => 'SVI', 'number' => '181', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Pokégear 3.0', 'set' => 'SVI', 'number' => '186', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Counter Catcher', 'set' => 'PAR', 'number' => '160', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Night Stretcher', 'set' => 'SFA', 'number' => '61', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Pal Pad', 'set' => 'SVI', 'number' => '182', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Superior Energy Retrieval', 'set' => 'PAL', 'number' => '189', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Super Rod', 'set' => 'PAL', 'number' => '188', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Brilliant Blender', 'set' => 'SSP', 'number' => '164', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],

            // Trainer — Tool (5)
            ['name' => 'Ancient Booster Energy Capsule', 'set' => 'TEF', 'number' => '140', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'tool'],
            ['name' => 'Exp. Share', 'set' => 'SVI', 'number' => '174', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],

            // Trainer — Stadium (2)
            ['name' => 'Artazon', 'set' => 'PAL', 'number' => '171', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'stadium'],

            // Energy (7)
            ['name' => 'Darkness Energy', 'set' => 'SVE', 'number' => '7', 'quantity' => 7, 'type' => 'energy', 'subtype' => null],
        ];
    }

    private function getAncientBoxRawDeckList(): string
    {
        return <<<'PTCG'
            Pokémon: 13
            4 Flutter Mane TEF 78
            4 Roaring Moon TEF 109
            1 Roaring Moon ex PR-SV 67
            1 Great Tusk TEF 97
            1 Koraidon SSP 116
            1 Munkidori TWM 95
            1 Pecharunt ex SFA 85

            Trainer: 40
            4 Professor Sada's Vitality PAR 170
            4 Explorer's Guidance TEF 147
            2 Boss's Orders PAL 172
            2 Surfer SSP 187
            2 Janine's Secret Art PRE 112
            2 Professor's Research PAF 87
            4 Earthen Vessel PAR 163
            3 Nest Ball SVI 181
            2 Pokégear 3.0 SVI 186
            2 Counter Catcher PAR 160
            2 Night Stretcher SFA 61
            1 Pal Pad SVI 182
            1 Superior Energy Retrieval PAL 189
            1 Super Rod PAL 188
            1 Brilliant Blender SSP 164
            4 Ancient Booster Energy Capsule TEF 140
            1 Exp. Share SVI 174
            2 Artazon PAL 171

            Energy: 7
            7 Darkness Energy SVE 7

            Total Cards: 60
            PTCG;
    }

    private function createRegidragoDeckVersion(ObjectManager $manager, Deck $deck): void
    {
        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(1);
        $version->setRawList($this->getRegidragoRawDeckList());

        foreach ($this->getRegidragoDeckCards() as $cardData) {
            $card = new DeckCard();
            $card->setCardName($cardData['name']);
            $card->setSetCode($cardData['set']);
            $card->setCardNumber($cardData['number']);
            $card->setQuantity($cardData['quantity']);
            $card->setCardType($cardData['type']);

            $version->addCard($card);
        }

        $manager->persist($version);

        $deck->setCurrentVersion($version);
    }

    /**
     * @return list<array{name: string, set: string, number: string, quantity: int, type: string, subtype: string|null}>
     */
    private function getRegidragoDeckCards(): array
    {
        return [
            // Pokemon (19)
            ['name' => 'Regidrago V', 'set' => 'SIT', 'number' => '135', 'quantity' => 4, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Regidrago VSTAR', 'set' => 'SIT', 'number' => '136', 'quantity' => 3, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Dialga-GX', 'set' => 'UPR', 'number' => '100', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Kyurem', 'set' => 'SFA', 'number' => '47', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Dragapult ex', 'set' => 'TWM', 'number' => '130', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Salamence ex', 'set' => 'JTG', 'number' => '114', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Noivern-GX', 'set' => 'BUS', 'number' => '99', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Noivern ex', 'set' => 'PAL', 'number' => '153', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Dedenne-GX', 'set' => 'UNB', 'number' => '57', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Crobat V', 'set' => 'DAA', 'number' => '104', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Tapu Lele-GX', 'set' => 'GRI', 'number' => '60', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Latias ex', 'set' => 'SSP', 'number' => '76', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Budew', 'set' => 'PRE', 'number' => '4', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Wobbuffet', 'set' => 'PHF', 'number' => '36', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],

            // Trainer — Supporter (11)
            ['name' => "Professor's Research", 'set' => 'JTG', 'number' => '155', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Guzma', 'set' => 'BUS', 'number' => '115', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'N', 'set' => 'FCO', 'number' => '105', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Iono', 'set' => 'PAL', 'number' => '185', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Marnie', 'set' => 'SSH', 'number' => '169', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Crispin', 'set' => 'SCR', 'number' => '133', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Raihan', 'set' => 'EVS', 'number' => '152', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Faba', 'set' => 'LOT', 'number' => '173', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],

            // Trainer — Item (16)
            ['name' => 'Mysterious Treasure', 'set' => 'FLI', 'number' => '113', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Quick Ball', 'set' => 'FST', 'number' => '237', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'VS Seeker', 'set' => 'PHF', 'number' => '109', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Battle Compressor', 'set' => 'PHF', 'number' => '92', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Hisuian Heavy Ball', 'set' => 'ASR', 'number' => '146', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Megaton Blower', 'set' => 'SSP', 'number' => '182', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Super Rod', 'set' => 'PAL', 'number' => '188', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],

            // Trainer — Tool (2)
            ['name' => 'Muscle Band', 'set' => 'XY', 'number' => '121', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],
            ['name' => 'Leafy Camo Poncho', 'set' => 'SIT', 'number' => '160', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],

            // Trainer — Stadium (2)
            ['name' => 'Parallel City', 'set' => 'BKT', 'number' => '145', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'stadium'],
            ['name' => 'Path to the Peak', 'set' => 'CRE', 'number' => '148', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'stadium'],

            // Energy (10)
            ['name' => 'Double Dragon Energy', 'set' => 'ROS', 'number' => '97', 'quantity' => 4, 'type' => 'energy', 'subtype' => null],
            ['name' => 'Grass Energy', 'set' => 'SVE', 'number' => '1', 'quantity' => 4, 'type' => 'energy', 'subtype' => null],
            ['name' => 'Fire Energy', 'set' => 'SVE', 'number' => '2', 'quantity' => 2, 'type' => 'energy', 'subtype' => null],
        ];
    }

    private function getRegidragoRawDeckList(): string
    {
        return <<<'PTCG'
            Pokémon: 19
            4 Regidrago V SIT 135
            3 Regidrago VSTAR SIT 136
            1 Dialga-GX UPR 100
            1 Kyurem SFA 47
            1 Dragapult ex TWM 130
            1 Salamence ex JTG 114
            1 Noivern-GX BUS 99
            1 Noivern ex PAL 153
            1 Dedenne-GX UNB 57
            1 Crobat V DAA 104
            1 Tapu Lele-GX GRI 60
            1 Latias ex SSP 76
            1 Budew PRE 4
            1 Wobbuffet PHF 36

            Trainer: 31
            3 Professor's Research JTG 155
            2 Guzma BUS 115
            1 N FCO 105
            1 Iono PAL 185
            1 Marnie SSH 169
            1 Crispin SCR 133
            1 Raihan EVS 152
            1 Faba LOT 173
            4 Mysterious Treasure FLI 113
            4 Quick Ball FST 237
            3 VS Seeker PHF 109
            2 Battle Compressor PHF 92
            1 Hisuian Heavy Ball ASR 146
            1 Megaton Blower SSP 182
            1 Super Rod PAL 188
            1 Muscle Band XY 121
            1 Leafy Camo Poncho SIT 160
            1 Parallel City BKT 145
            1 Path to the Peak CRE 148

            Energy: 10
            4 Double Dragon Energy ROS 97
            4 Grass Energy SVE 1
            2 Fire Energy SVE 2

            Total Cards: 60
            PTCG;
    }

    private function createLugiaArcheopsDeckVersion(ObjectManager $manager, Deck $deck): void
    {
        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(1);
        $version->setRawList($this->getLugiaArcheopsRawDeckList());

        foreach ($this->getLugiaArcheopsDeckCards() as $cardData) {
            $card = new DeckCard();
            $card->setCardName($cardData['name']);
            $card->setSetCode($cardData['set']);
            $card->setCardNumber($cardData['number']);
            $card->setQuantity($cardData['quantity']);
            $card->setCardType($cardData['type']);

            $version->addCard($card);
        }

        $manager->persist($version);

        $deck->setCurrentVersion($version);
    }

    /**
     * @return list<array{name: string, set: string, number: string, quantity: int, type: string, subtype: string|null}>
     */
    private function getLugiaArcheopsDeckCards(): array
    {
        return [
            // Pokemon (16)
            ['name' => 'Lugia V', 'set' => 'SIT', 'number' => '138', 'quantity' => 3, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Lugia VSTAR', 'set' => 'SIT', 'number' => '139', 'quantity' => 2, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Archeops', 'set' => 'SIT', 'number' => '147', 'quantity' => 3, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Garchomp & Giratina-GX', 'set' => 'UNM', 'number' => '146', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Naganadel & Guzzlord-GX', 'set' => 'CEC', 'number' => '158', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Garchomp V', 'set' => 'ASR', 'number' => '117', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Koraidon ex', 'set' => 'TEF', 'number' => '120', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Kyurem', 'set' => 'SFA', 'number' => '47', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Dedenne-GX', 'set' => 'UNB', 'number' => '57', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Crobat V', 'set' => 'DAA', 'number' => '104', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Tapu Lele-GX', 'set' => 'GRI', 'number' => '60', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],

            // Trainer — Supporter (8)
            ['name' => 'Wally', 'set' => 'ROS', 'number' => '94', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Thorton', 'set' => 'LOR', 'number' => '167', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Professor Juniper', 'set' => 'PLB', 'number' => '84', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'N', 'set' => 'FCO', 'number' => '105', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Marnie', 'set' => 'SSH', 'number' => '169', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Guzma', 'set' => 'BUS', 'number' => '115', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Faba', 'set' => 'LOT', 'number' => '173', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],

            // Trainer — Item (22)
            ['name' => 'Quick Ball', 'set' => 'FST', 'number' => '237', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Ultra Ball', 'set' => 'SVI', 'number' => '196', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Battle Compressor', 'set' => 'PHF', 'number' => '92', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'VS Seeker', 'set' => 'PHF', 'number' => '109', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => "Trainers' Mail", 'set' => 'ROS', 'number' => '92', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Special Charge', 'set' => 'STS', 'number' => '105', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Field Blower', 'set' => 'GRI', 'number' => '125', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Secret Box', 'set' => 'TWM', 'number' => '163', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],

            // Trainer — Tool (1)
            ['name' => 'Stealthy Hood', 'set' => 'UNB', 'number' => '186', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],

            // Trainer — Stadium (3)
            ['name' => 'Silent Lab', 'set' => 'PRC', 'number' => '140', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'stadium'],

            // Energy (8)
            ['name' => 'Double Colorless Energy', 'set' => 'SUM', 'number' => '136', 'quantity' => 4, 'type' => 'energy', 'subtype' => null],
            ['name' => 'Double Dragon Energy', 'set' => 'ROS', 'number' => '97', 'quantity' => 4, 'type' => 'energy', 'subtype' => null],
        ];
    }

    private function getLugiaArcheopsRawDeckList(): string
    {
        return <<<'PTCG'
            Pokémon: 16
            3 Lugia V SIT 138
            2 Lugia VSTAR SIT 139
            3 Archeops SIT 147
            1 Garchomp & Giratina-GX UNM 146
            1 Naganadel & Guzzlord-GX CEC 158
            1 Garchomp V ASR 117
            1 Koraidon ex TEF 120
            1 Kyurem SFA 47
            1 Dedenne-GX UNB 57
            1 Crobat V DAA 104
            1 Tapu Lele-GX GRI 60

            Trainer: 36
            2 Wally ROS 94
            1 Thorton LOR 167
            1 Professor Juniper PLB 84
            1 N FCO 105
            1 Marnie SSH 169
            1 Guzma BUS 115
            1 Faba LOT 173
            4 Quick Ball FST 237
            4 Ultra Ball SVI 196
            4 Battle Compressor PHF 92
            3 VS Seeker PHF 109
            3 Trainers' Mail ROS 92
            3 Special Charge STS 105
            2 Field Blower GRI 125
            1 Secret Box TWM 163
            1 Stealthy Hood UNB 186
            3 Silent Lab PRC 140

            Energy: 8
            4 Double Colorless Energy SUM 136
            4 Double Dragon Energy ROS 97

            Total Cards: 60
            PTCG;
    }

    /**
     * @see docs/features.md F4.8 — Staff-delegated lending
     */
    private function createDeckRegistrations(ObjectManager $manager, Event $event, Deck $ironThorns, Deck $ancientBox, Deck $regidrago): void
    {
        $registration = new EventDeckRegistration();
        $registration->setEvent($event);
        $registration->setDeck($ironThorns);
        $registration->setDelegateToStaff(true);
        $manager->persist($registration);

        $ancientBoxReg = new EventDeckRegistration();
        $ancientBoxReg->setEvent($event);
        $ancientBoxReg->setDeck($ancientBox);
        $ancientBoxReg->setDelegateToStaff(false);
        $manager->persist($ancientBoxReg);

        $regidragoReg = new EventDeckRegistration();
        $regidragoReg->setEvent($event);
        $regidragoReg->setDeck($regidrago);
        $regidragoReg->setDelegateToStaff(true);
        $manager->persist($regidragoReg);
    }

    /**
     * @see docs/features.md F4.1 — Request to borrow a deck
     * @see docs/features.md F4.9 — Staff deck custody tracking
     * @see docs/features.md F4.10 — Owner borrow inbox
     */
    /**
     * @return Borrow the pending borrow on today's event (for notification fixtures)
     */
    private function createBorrowFixtures(ObjectManager $manager, Event $todayEvent, Event $futureEvent, User $borrower, User $lender, User $admin, Deck $ironThorns, Deck $ancientBox, Deck $lenderDeck, User $staff1): Borrow
    {
        $ironThornsVersion = $ironThorns->getCurrentVersion();
        \assert(null !== $ironThornsVersion);

        $ancientBoxVersion = $ancientBox->getCurrentVersion();
        \assert(null !== $ancientBoxVersion);

        // --- Today's event: 1 pending + 1 approved (borrower borrows from admin) ---

        $pendingBorrow = new Borrow();
        $pendingBorrow->setDeck($ironThorns);
        $pendingBorrow->setDeckVersion($ironThornsVersion);
        $pendingBorrow->setBorrower($borrower);
        $pendingBorrow->setEvent($todayEvent);
        $pendingBorrow->setNotes('Need it for round 1');
        $manager->persist($pendingBorrow);

        $approvedBorrow = new Borrow();
        $approvedBorrow->setDeck($ancientBox);
        $approvedBorrow->setDeckVersion($ancientBoxVersion);
        $approvedBorrow->setBorrower($borrower);
        $approvedBorrow->setEvent($todayEvent);
        $approvedBorrow->setStatus(BorrowStatus::Approved);
        $approvedBorrow->setApprovedAt(new \DateTimeImmutable());
        $approvedBorrow->setApprovedBy($admin);
        $manager->persist($approvedBorrow);

        // --- Future event: 1 pending (lender borrows Iron Thorns from admin) ---
        // Gives 2 total pending borrows across events to exercise inbox grouping.
        // Note: Ancient Box is kept free at this event for EventControllerTest scenarios.

        $pendingBorrow2 = new Borrow();
        $pendingBorrow2->setDeck($ironThorns);
        $pendingBorrow2->setDeckVersion($ironThornsVersion);
        $pendingBorrow2->setBorrower($lender);
        $pendingBorrow2->setEvent($futureEvent);
        $pendingBorrow2->setNotes('Would love to try this deck at Lyon');
        $manager->persist($pendingBorrow2);

        // --- Today's event: 1 delegated pending (staff1 borrows Regidrago from lender) ---
        $lenderDeckVersion = $lenderDeck->getCurrentVersion();
        \assert(null !== $lenderDeckVersion);

        $delegatedBorrow = new Borrow();
        $delegatedBorrow->setDeck($lenderDeck);
        $delegatedBorrow->setDeckVersion($lenderDeckVersion);
        $delegatedBorrow->setBorrower($staff1);
        $delegatedBorrow->setEvent($todayEvent);
        $delegatedBorrow->setIsDelegatedToStaff(true);
        $delegatedBorrow->setNotes('Delegated to staff for event');
        $manager->persist($delegatedBorrow);

        // Deck stays Available — approval no longer sets Reserved (per-event concern)

        // --- Today's event: 1 lent borrow (borrower borrows "Lent Fixture Deck" from lender) ---
        // Dedicated deck for ending phase / overdue transition testing.
        $lentFixtureDeck = $this->createDeck($manager, $lender, 'Lent Fixture Deck', DeckStatus::Lent);
        $lentFixtureVersion = new DeckVersion();
        $lentFixtureVersion->setDeck($lentFixtureDeck);
        $lentFixtureVersion->setVersionNumber(1);
        $lentFixtureVersion->setRawList('');
        $manager->persist($lentFixtureVersion);
        $lentFixtureDeck->setCurrentVersion($lentFixtureVersion);

        $lentBorrow = new Borrow();
        $lentBorrow->setDeck($lentFixtureDeck);
        $lentBorrow->setDeckVersion($lentFixtureVersion);
        $lentBorrow->setBorrower($borrower);
        $lentBorrow->setEvent($todayEvent);
        $lentBorrow->setStatus(BorrowStatus::Lent);
        $lentBorrow->setApprovedAt(new \DateTimeImmutable('-1 hour'));
        $lentBorrow->setApprovedBy($lender);
        $lentBorrow->setHandedOffAt(new \DateTimeImmutable('-30 minutes'));
        $lentBorrow->setHandedOffBy($lender);
        $manager->persist($lentBorrow);

        return $pendingBorrow;
    }

    private function createAdminNotifications(ObjectManager $manager, User $admin, User $borrower, Event $event, Borrow $pendingBorrow): void
    {
        $n1 = new Notification();
        $n1->setRecipient($admin);
        $n1->setType(NotificationType::BorrowRequested);
        $n1->setTitle('New borrow request from '.$borrower->getScreenName());
        $n1->setMessage($borrower->getScreenName().' wants to borrow Iron Thorns for '.$event->getName().'.');
        $n1->setContext([
            'borrowId' => $pendingBorrow->getId(),
            'deckId' => $pendingBorrow->getDeck()->getId(),
            'eventId' => $event->getId(),
        ]);
        $manager->persist($n1);

        $n2 = new Notification();
        $n2->setRecipient($admin);
        $n2->setType(NotificationType::StaffAssigned);
        $n2->setTitle('You were assigned as staff');
        $n2->setMessage('You have been assigned as staff for '.$event->getName().'.');
        $n2->setContext(['eventId' => $event->getId()]);
        $manager->persist($n2);

        $n3 = new Notification();
        $n3->setRecipient($admin);
        $n3->setType(NotificationType::EventInvited);
        $n3->setTitle('You are invited to an event');
        $n3->setMessage('You have been invited to '.$event->getName().'. Check it out!');
        $n3->setContext(['eventId' => $event->getId()]);
        $n3->setIsRead(true);
        $manager->persist($n3);
    }

    /**
     * Content-channel CMS pages, mirroring the production menu structure
     * (About, News, Getting started, footer Legal) with fixture-only copy.
     *
     * The banned/staple listing intro pages are not seeded here: `make fixtures`
     * runs `app:listings:seed-intros` like the production entrypoint, and the
     * functional tests seed their own intro pages.
     */
    private function createCmsFixtures(ObjectManager $manager, Channel $contentChannel): void
    {
        $aboutCategory = $this->createMenuCategory($manager, $contentChannel, 0, false, 'About', 'À propos');
        $newsCategory = $this->createMenuCategory($manager, $contentChannel, 1, false, 'News', 'Actualités');
        $gettingStartedCategory = $this->createMenuCategory($manager, $contentChannel, 2, false, 'Getting started', 'Pour commencer');
        $legalCategory = $this->createMenuCategory($manager, $contentChannel, 1, true, 'Legal', 'Légal');

        // Embedded on the homepage; not listed in any menu.
        $this->createPage($manager, $contentChannel, 'welcome', null, '-10 months', '-1 month', [
            'en' => ['Welcome to Dowsing Machine', <<<'MD'
                Dowsing Machine is a guide to the **Pokémon TCG Expanded format**. Browse the archetypes and their variants, check sample deck lists, and see which cards are banned and which ones show up in almost every deck.
                MD],
            'fr' => ['Bienvenue sur Dowsing Machine', <<<'MD'
                Dowsing Machine est un guide du **format Expanded de Pokémon TCG**. Parcours les archétypes et leurs variantes, consulte des deck lists d'exemple, et vois quelles cartes sont bannies et lesquelles reviennent dans presque tous les decks.
                MD],
        ]);

        $this->createPage($manager, $contentChannel, 'about-dowsing-machine', $aboutCategory, '-1 year', '-2 months', [
            'en' => ['About Dowsing Machine', <<<'MD'
                Dowsing Machine is written by a small group of Expanded players. Card data comes from [TCGdex](https://tcgdex.dev/), and every page goes through a translation review before it is published in another language.

                The site started as Expanded Decks, a tool to lend physical decks at events. The archetype pages ended up being what people came for, so the project now focuses on them.

                The code is open source on [GitHub](https://github.com/jbourdin/dowsingMachine).
                MD],
            'fr' => ['À propos de Dowsing Machine', <<<'MD'
                Dowsing Machine est écrit par un petit groupe de joueur·euses Expanded. Les données des cartes viennent de [TCGdex](https://tcgdex.dev/), et chaque page passe par une relecture avant d'être publiée dans une autre langue.

                Le site a commencé sous le nom d'Expanded Decks, un outil pour prêter des decks physiques en tournoi. Les gens venaient surtout pour les pages d'archétypes, alors le projet se concentre maintenant dessus.

                Le code est open source sur [GitHub](https://github.com/jbourdin/dowsingMachine).
                MD],
        ], position: 1);

        // --- News (≥ 8 published pages with en + fr: PageRepositoryTest relies on it) ---

        $newsPages = [
            ['season-2026-kickoff', '-5 months', null,
                ['Season 2026 is here!', <<<'MD'
                    No rotation in Expanded this year: every card from **Black & White** onward stays legal, minus the banned list. The new sets add a few cards worth testing, and we will update the archetype pages as results come in.
                    MD],
                ['La saison 2026 est lancée !', <<<'MD'
                    Pas de rotation en Expanded cette année : toutes les cartes depuis **Noir & Blanc** restent légales, à part la liste des bannies. Les nouvelles extensions apportent quelques cartes à tester, et on mettra à jour les pages d'archétypes au fil des résultats.
                    MD]],
            ['tcg-live-expanded-friend-games', '-4 months', '-6 weeks',
                ['Expanded friend games come to TCG Live', <<<'MD'
                    You can now challenge a friend in Expanded on TCG Live. Ranked play is still Standard only, but it makes testing a new list a lot easier.
                    MD],
                ['Les parties amicales Expanded arrivent sur TCG Live', <<<'MD'
                    Tu peux maintenant défier un·e ami·e en Expanded sur TCG Live. Le classé reste en Standard, mais tester une nouvelle liste devient beaucoup plus simple.
                    MD]],
            ['champions-league-aichi-review', '-3 months', null,
                ['Champions League Aichi: a look at the results', <<<'MD'
                    The top cut lists from Aichi are in. We went through them and noted the cards that could cross over to Expanded.
                    MD],
                ['Champions League Aichi : retour sur les résultats', <<<'MD'
                    Les listes du top cut d'Aichi sont publiées. On les a passées en revue pour repérer les cartes qui pourraient servir en Expanded.
                    MD]],
            ['march-league-challenge', '-2 months', null,
                ['March League Challenge recap', <<<'MD'
                    **Regidrago** took the event again, with **Iron Thorns ex** close behind. Both lists are on their archetype pages.
                    MD],
                ['Résumé du League Challenge de mars', <<<'MD'
                    **Regidrago** remporte encore l'événement, suivi de près par **Iron Thorns ex**. Les deux listes sont sur leurs pages d'archétype.
                    MD]],
            ['lumiose-tour-2026', '-6 weeks', null,
                ['The Lumiose Tour 2026', <<<'MD'
                    A new online tournament series, played in Expanded. Here is how the qualifiers work and how to sign up.
                    MD],
                ['Le Lumiose Tour 2026', <<<'MD'
                    Une nouvelle série de tournois en ligne, jouée en Expanded. Voici comment fonctionnent les qualifications et comment t'inscrire.
                    MD]],
            ['lumiose-tour-2026-review', '-3 weeks', '-5 days',
                ['Lumiose Tour 2026: results and review', <<<'MD'
                    The final standings, the decks that made the top 8, and what the event tells us about the format.
                    MD],
                ['Lumiose Tour 2026 : résultats et analyse', <<<'MD'
                    Le classement final, les decks du top 8, et ce que l'événement nous apprend sur le format.
                    MD]],
            ['banned-list-explained', '-12 days', null,
                ['Every ban, explained', <<<'MD'
                    The [banned cards](/en/banned-cards) page now gives the reason behind each ban and links to the official announcement.
                    MD],
                ['Chaque ban, expliqué', <<<'MD'
                    La page des [cartes bannies](/fr/banned-cards) donne maintenant la raison de chaque ban et renvoie vers l'annonce officielle.
                    MD]],
            ['compare-archetype-variants', '-3 days', null,
                ['Compare archetype variants side by side', <<<'MD'
                    Archetype pages with several variants now have a compare view: pick two lists and see which cards differ.
                    MD],
                ['Compare les variantes d\'un archétype', <<<'MD'
                    Les pages d'archétype avec plusieurs variantes ont maintenant une vue de comparaison : choisis deux listes et vois les cartes qui changent.
                    MD]],
        ];
        foreach ($newsPages as [$slug, $firstPublishedAt, $lastPublishedAt, $english, $french]) {
            $this->createPage($manager, $contentChannel, $slug, $newsCategory, $firstPublishedAt, $lastPublishedAt, ['en' => $english, 'fr' => $french]);
        }

        // --- Getting started ---

        $this->createPage($manager, $contentChannel, 'what-is-expanded', $gettingStartedCategory, '-11 months', '-2 months', [
            'en' => ['What is Expanded?', <<<'MD'
                Expanded is a Pokémon TCG format where every card from **Black & White** onward is legal, except for a short banned list. Decks follow the usual rules: 60 cards, at most 4 copies of a card except basic Energy.

                The large card pool means old combos are still around, which is why the format feels so different from Standard.
                MD],
            'fr' => ['C\'est quoi, l\'Expanded ?', <<<'MD'
                L'Expanded est un format de Pokémon TCG où toutes les cartes depuis **Noir & Blanc** sont légales, sauf une courte liste de cartes bannies. Les decks suivent les règles habituelles : 60 cartes, 4 exemplaires maximum d'une même carte hors Énergies de base.

                Avec un tel pool de cartes, de vieux combos tournent toujours, et c'est ce qui rend le format si différent du Standard.
                MD],
        ], position: 1);

        $this->createPage($manager, $contentChannel, 'types-of-cards', $gettingStartedCategory, '-11 months', null, [
            'en' => ['Pokémon EX, GX, V and more: how do they work?', <<<'MD'
                Expanded mixes several generations of special Pokémon. Most of them (EX, GX, V, VSTAR, ex) give up **2 prize cards** when knocked out; VMAX gives up 3.

                GX attacks and VSTAR Powers can each be used once per game, so timing them is a big part of playing these decks.
                MD],
            'fr' => ['Pokémon EX, GX, V et compagnie : comment ça marche ?', <<<'MD'
                L'Expanded mélange plusieurs générations de Pokémon spéciaux. La plupart (EX, GX, V, VSTAR, ex) donnent **2 cartes Récompense** quand ils sont mis K.O. ; les VMAX en donnent 3.

                Les attaques GX et les Pouvoirs VSTAR ne s'utilisent qu'une fois par partie, donc bien choisir le moment compte beaucoup avec ces decks.
                MD],
        ], position: 2);

        // --- Footer: Legal ---

        $this->createPage($manager, $contentChannel, 'legal', $legalCategory, '-13 months', '-4 months', [
            'en' => ['Legal notice', <<<'MD'
                ## Site owner

                This website is operated by [Julien Bourdin](https://github.com/jbourdin) as a non-commercial, open-source project. Source code: [github.com/jbourdin/dowsingMachine](https://github.com/jbourdin/dowsingMachine).

                ## Hosting

                Hosted by [Scaleway](https://www.scaleway.com/), 8 rue de la Ville-l'Évêque, 75008 Paris, France, behind the [Bunny.net](https://bunny.net/) CDN.

                ## Contact

                For any question about this website, [open an issue](https://github.com/jbourdin/dowsingMachine/issues) on GitHub.

                ## Intellectual property

                Pokémon and Pokémon TCG are trademarks of Nintendo, Creatures Inc. and GAME FREAK Inc. This project is not affiliated with, endorsed or sponsored by any of them. Card data comes from the [TCGdex API](https://tcgdex.dev/).
                MD],
            'fr' => ['Mentions légales', <<<'MD'
                ## Éditeur du site

                Ce site est édité par [Julien Bourdin](https://github.com/jbourdin), dans le cadre d'un projet open source non commercial. Code source : [github.com/jbourdin/dowsingMachine](https://github.com/jbourdin/dowsingMachine).

                ## Hébergement

                Hébergé par [Scaleway](https://www.scaleway.com/), 8 rue de la Ville-l'Évêque, 75008 Paris, France, derrière le CDN [Bunny.net](https://bunny.net/).

                ## Contact

                Pour toute question sur ce site, [ouvre une issue](https://github.com/jbourdin/dowsingMachine/issues) sur GitHub.

                ## Propriété intellectuelle

                Pokémon et Pokémon TCG sont des marques de Nintendo, Creatures Inc. et GAME FREAK Inc. Ce projet n'est ni affilié, ni approuvé, ni sponsorisé par ces sociétés. Les données des cartes viennent de l'[API TCGdex](https://tcgdex.dev/).
                MD],
        ], position: 1);

        // --- Draft page (preview-only, used by PageControllerTest) ---

        $draftPage = $this->createPage($manager, $contentChannel, 'upcoming-features', null, null, null, [
            'en' => ['Upcoming features (draft)', <<<'MD'
                > **This page is a draft and not visible to regular users.**

                Planned: a matchup table on archetype pages, and French versions of the Getting started guides.
                MD],
        ]);
        $draftPage->setNoIndex(true);
    }

    private function createMenuCategory(ObjectManager $manager, Channel $channel, int $position, bool $isFooter, string $nameEn, string $nameFr): MenuCategory
    {
        $category = new MenuCategory();
        $category->setPosition($position);
        $category->setIsFooter($isFooter);
        $category->setChannel($channel);
        $manager->persist($category);

        foreach (['en' => $nameEn, 'fr' => $nameFr] as $locale => $name) {
            $translation = new MenuCategoryTranslation();
            $translation->setMenuCategory($category);
            $translation->setLocale($locale);
            $translation->setName($name);
            $category->addTranslation($translation);
            $manager->persist($translation);
        }

        return $category;
    }

    /**
     * Creates a page attributed to the content author (F19.8). A null
     * `$firstPublishedAt` leaves the page unpublished.
     *
     * @param array<string, array{0: string, 1: string}> $translations locale => [title, Markdown content]
     */
    private function createPage(ObjectManager $manager, Channel $channel, string $slug, ?MenuCategory $category, ?string $firstPublishedAt, ?string $lastPublishedAt, array $translations, int $position = 0): Page
    {
        $page = new Page();
        $page->setChannel($channel);
        $page->setSlug($slug);
        $page->setMenuCategory($category);
        $page->setPosition($position);
        $page->setIsPublished(null !== $firstPublishedAt);
        $page->setAuthor($this->contentAuthor);
        if (null !== $firstPublishedAt) {
            $this->backdatePublication(
                $page,
                new \DateTimeImmutable($firstPublishedAt),
                null !== $lastPublishedAt ? new \DateTimeImmutable($lastPublishedAt) : null,
            );
        }
        $manager->persist($page);

        foreach ($translations as $locale => [$title, $content]) {
            $translation = new PageTranslation();
            $translation->setPage($page);
            $translation->setLocale($locale);
            $translation->setTitle($title);
            $translation->setContent($content);
            $page->addTranslation($translation);
            $manager->persist($translation);
        }

        return $page;
    }

    private function createCharizardFlareonDeckVersion(ObjectManager $manager, Deck $deck): void
    {
        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(1);
        $version->setRawList($this->getCharizardFlareonRawDeckList());

        foreach ($this->getCharizardFlareonDeckCards() as $cardData) {
            $card = new DeckCard();
            $card->setCardName($cardData['name']);
            $card->setSetCode($cardData['set']);
            $card->setCardNumber($cardData['number']);
            $card->setQuantity($cardData['quantity']);
            $card->setCardType($cardData['type']);

            $version->addCard($card);
        }

        $manager->persist($version);

        $deck->setCurrentVersion($version);
    }

    /**
     * @return list<array{name: string, set: string, number: string, quantity: int, type: string, subtype: string|null}>
     */
    private function getCharizardFlareonDeckCards(): array
    {
        return [
            // Pokemon (12)
            ['name' => 'Charizard ex', 'set' => 'OBF', 'number' => '125', 'quantity' => 3, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Charmeleon', 'set' => 'OBF', 'number' => '26', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Charmander', 'set' => 'OBF', 'number' => '25', 'quantity' => 4, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Flareon', 'set' => 'VIV', 'number' => '26', 'quantity' => 2, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Eevee', 'set' => 'VIV', 'number' => '130', 'quantity' => 2, 'type' => 'pokemon', 'subtype' => null],

            // Trainer — Supporter (12)
            ['name' => 'Professor\'s Research', 'set' => 'SVI', 'number' => '189', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Boss\'s Orders', 'set' => 'PAL', 'number' => '172', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Arven', 'set' => 'SVI', 'number' => '166', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Cynthia\'s Ambition', 'set' => 'BRS', 'number' => '138', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'supporter'],

            // Trainer — Item (24)
            ['name' => 'Rare Candy', 'set' => 'SVI', 'number' => '191', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Ultra Ball', 'set' => 'SVI', 'number' => '196', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Battle VIP Pass', 'set' => 'FST', 'number' => '225', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Nest Ball', 'set' => 'SVI', 'number' => '181', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Super Rod', 'set' => 'PAL', 'number' => '188', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Pal Pad', 'set' => 'SVI', 'number' => '182', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],

            // Trainer — Stadium (3)
            ['name' => 'Magma Basin', 'set' => 'BRS', 'number' => '144', 'quantity' => 3, 'type' => 'trainer', 'subtype' => 'stadium'],

            // Energy (8)
            ['name' => 'Fire Energy', 'set' => 'SVE', 'number' => '2', 'quantity' => 8, 'type' => 'energy', 'subtype' => null],
        ];
    }

    private function getCharizardFlareonRawDeckList(): string
    {
        return <<<'DECK'
Pokémon: 12
3 Charizard ex OBF 125
1 Charmeleon OBF 26
4 Charmander OBF 25
2 Flareon VIV 26
2 Eevee VIV 130

Trainer: 39
4 Professor's Research SVI 189
3 Boss's Orders PAL 172
2 Arven SVI 166
3 Cynthia's Ambition BRS 138
4 Rare Candy SVI 191
4 Ultra Ball SVI 196
4 Battle VIP Pass FST 225
3 Nest Ball SVI 181
2 Super Rod PAL 188
2 Pal Pad SVI 182
3 Magma Basin BRS 144

Energy: 8
8 Fire Energy SVE 2

Total Cards: 59
DECK;
    }

    /**
     * Build a PTCGL-formatted raw deck list from a card data array.
     *
     * @param array<int, array{name: string, set: string, number: string, quantity: int, type: string, subtype: string|null}> $cards
     */
    private function buildRawList(array $cards): string
    {
        $lines = [];

        foreach ($cards as $card) {
            $lines[] = \sprintf('%d %s %s %s', $card['quantity'], $card['name'], $card['set'], $card['number']);
        }

        return implode("\n", $lines);
    }

    /**
     * Apply quantity overrides to a card data array (by card name).
     */
    private function createShadowRiderDeckVersion(ObjectManager $manager, Deck $deck): void
    {
        $version = new DeckVersion();
        $version->setDeck($deck);
        $version->setVersionNumber(1);
        $version->setRawList($this->getShadowRiderRawDeckList());

        foreach ($this->getShadowRiderDeckCards() as $cardData) {
            $card = new DeckCard();
            $card->setCardName($cardData['name']);
            $card->setSetCode($cardData['set']);
            $card->setCardNumber($cardData['number']);
            $card->setQuantity($cardData['quantity']);
            $card->setCardType($cardData['type']);

            $version->addCard($card);
        }

        $manager->persist($version);

        $deck->setCurrentVersion($version);
    }

    /**
     * @return list<array{name: string, set: string, number: string, quantity: int, type: string, subtype: string|null}>
     */
    private function getShadowRiderDeckCards(): array
    {
        return [
            // Pokemon
            ['name' => 'Shadow Rider Calyrex V', 'set' => 'S6K', 'number' => '76', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Shadow Rider Calyrex V', 'set' => 'S6K', 'number' => '36', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Shadow Rider Calyrex V', 'set' => 'CRE', 'number' => '74', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Shadow Rider Calyrex VMAX', 'set' => 'ASR-TG', 'number' => '30', 'quantity' => 3, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Trevenant & Dusknoir-GX', 'set' => 'SMP', 'number' => '217', 'quantity' => 2, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Tapu Lele-GX', 'set' => 'GRI', 'number' => '155', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Tapu Lele-GX', 'set' => 'GRI', 'number' => '137', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => "Lillie's Clefairy ex", 'set' => 'JTG', 'number' => '56', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Budew', 'set' => 'PRE', 'number' => '4', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Chimecho', 'set' => 'CIN', 'number' => '43', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Girafarig', 'set' => 'SM8', 'number' => '45', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Wobbuffet', 'set' => 'PHF', 'number' => '36', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            ['name' => 'Latias ex', 'set' => 'SSP', 'number' => '220', 'quantity' => 1, 'type' => 'pokemon', 'subtype' => null],
            // Trainer - Supporter
            ['name' => 'Guzma', 'set' => 'BUS', 'number' => '115', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => "Professor's Research", 'set' => 'JTG', 'number' => '155', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Raihan', 'set' => 'CRZ', 'number' => '140', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Arven', 'set' => 'SVI', 'number' => '235', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Faba', 'set' => 'LOT', 'number' => '173', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Acerola', 'set' => 'BUS', 'number' => '112', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'N', 'set' => 'FCO', 'number' => '105', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Marnie', 'set' => 'SSH', 'number' => '169', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            ['name' => 'Tulip', 'set' => 'PAR', 'number' => '181', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'supporter'],
            // Trainer - Item
            ['name' => 'Fog Crystal', 'set' => 'CRE', 'number' => '140', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Mysterious Treasure', 'set' => 'FLI', 'number' => '113a', 'quantity' => 4, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'VS Seeker', 'set' => 'PHF', 'number' => '109', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Battle VIP Pass', 'set' => 'FST', 'number' => '225', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Night Stretcher', 'set' => 'SFA', 'number' => '61', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Hisuian Heavy Ball', 'set' => 'ASR', 'number' => '146', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Unfair Stamp', 'set' => 'TWM', 'number' => '165', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'Field Blower', 'set' => 'GRI', 'number' => '125', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            ['name' => 'VS Seeker', 'set' => 'ROS', 'number' => '110', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'item'],
            // Trainer - Tool
            ['name' => 'Float Stone', 'set' => 'BKT', 'number' => '137', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],
            ['name' => 'Forest Seal Stone', 'set' => 'SIT', 'number' => '156', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],
            ['name' => 'Fighting Fury Belt', 'set' => 'BKP', 'number' => '99', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],
            ['name' => 'Float Stone', 'set' => 'PLF', 'number' => '99', 'quantity' => 1, 'type' => 'trainer', 'subtype' => 'tool'],
            // Trainer - Stadium
            ['name' => 'Temple of Sinnoh', 'set' => 'ASR', 'number' => '155', 'quantity' => 2, 'type' => 'trainer', 'subtype' => 'stadium'],
            // Energy
            ['name' => 'Psychic Energy', 'set' => 'SVE', 'number' => '21', 'quantity' => 11, 'type' => 'energy', 'subtype' => null],
        ];
    }

    private function getShadowRiderRawDeckList(): string
    {
        return <<<'PTCG'
Pokémon: 16
1 Shadow Rider Calyrex V S6K 76
1 Shadow Rider Calyrex V S6K 36
1 Shadow Rider Calyrex V CRE 74
3 Shadow Rider Calyrex VMAX ASR-TG 30
2 Trevenant & Dusknoir-GX SMP 217
1 Tapu Lele-GX GRI 155
1 Tapu Lele-GX GRI 137
1 Lillie's Clefairy ex JTG 56
1 Budew PRE 4
1 Chimecho CIN 43
1 Girafarig SM8 45
1 Wobbuffet PHF 36
1 Latias ex SSP 220

Trainer: 33
2 Guzma BUS 115
1 Professor's Research JTG 155
1 Raihan CRZ 140
1 Arven SVI 235
1 Faba LOT 173
1 Acerola BUS 112
1 N FCO 105
1 Marnie SSH 169
1 Tulip PAR 181
4 Fog Crystal CRE 140
4 Mysterious Treasure FLI 113a
2 VS Seeker PHF 109
2 Battle VIP Pass FST 225
1 Night Stretcher SFA 61
1 Hisuian Heavy Ball ASR 146
1 Unfair Stamp TWM 165
1 Field Blower GRI 125
1 VS Seeker ROS 110
1 Float Stone BKT 137
1 Forest Seal Stone SIT 156
1 Fighting Fury Belt BKP 99
1 Float Stone PLF 99
2 Temple of Sinnoh ASR 155

Energy: 11
11 Psychic Energy SVE 21
PTCG;
    }

    /**
     * @param array<int, array{name: string, set: string, number: string, quantity: int, type: string, subtype: string|null}> &$cards
     * @param array<string, int>                                                                                              $overrides
     */
    private function applyQuantityOverrides(array &$cards, array $overrides): void
    {
        foreach ($cards as &$card) {
            if (isset($overrides[$card['name']])) {
                $card['quantity'] = $overrides[$card['name']];
            }
        }
    }

    /**
     * @see docs/features.md F10.3 — HomepageLayout entity and data model
     */
    private function createHomepageFixtures(ObjectManager $manager, Deck $featuredDeck, Event $featuredEvent, Channel $appChannel): void
    {
        $layout = new HomepageLayout();
        $layout->setIsPublished(true);
        $layout->setChannel($appChannel);
        $layout->setBlocks([
            [
                'type' => 'carousel',
                'columnWidth' => null,
                'cssClasses' => null,
                'startAt' => null,
                'endAt' => null,
                'items' => [
                    [
                        'image' => 'https://placehold.co/1200x400/2c3e7c/f0c040?text=Expanded+Format+Season+2026',
                        'alt' => 'Expanded Format Season 2026',
                        'link' => '/event',
                        'startAt' => null,
                        'endAt' => null,
                    ],
                    [
                        'image' => 'https://placehold.co/1200x400/1a1a2e/e0e0e0?text=Browse+the+Deck+Library',
                        'alt' => 'Browse the Deck Library',
                        'link' => '/deck',
                        'startAt' => null,
                        'endAt' => null,
                    ],
                    [
                        'image' => 'https://placehold.co/1200x400/3d1f5c/f5c542?text=Borrow+a+Deck+for+Your+Next+Event',
                        'alt' => 'Borrow a Deck for Your Next Event',
                        'link' => '/event',
                        'startAt' => null,
                        'endAt' => null,
                    ],
                ],
            ],
            [
                'type' => 'latestPages',
                'columnWidth' => null,
                'cssClasses' => null,
                'startAt' => null,
                'endAt' => null,
                'categorySlug' => 'news',
                'limit' => 5,
            ],
            [
                'type' => 'featuredDeck',
                'columnWidth' => 6,
                'cssClasses' => null,
                'startAt' => null,
                'endAt' => null,
                'shortTag' => $featuredDeck->getShortTag(),
            ],
            [
                'type' => 'featuredEvent',
                'columnWidth' => 6,
                'cssClasses' => null,
                'startAt' => null,
                'endAt' => null,
                'eventId' => $featuredEvent->getId(),
            ],
        ]);
        $manager->persist($layout);

        $translationEn = new HomepageLayoutTranslation();
        $translationEn->setHomepageLayout($layout);
        $translationEn->setLocale('en');
        $translationEn->setBlockTranslations([
            '2' => [
                'title' => 'Deck of the month',
                'description' => 'The most played deck in the format right now.',
            ],
            '3' => [
                'title' => 'Next event',
                'description' => 'Join us for the next Expanded format event!',
            ],
        ]);
        $layout->addTranslation($translationEn);
        $manager->persist($translationEn);

        $translationFr = new HomepageLayoutTranslation();
        $translationFr->setHomepageLayout($layout);
        $translationFr->setLocale('fr');
        $translationFr->setBlockTranslations([
            '2' => [
                'title' => 'Deck du mois',
                'description' => 'Le deck le plus joué du format en ce moment.',
            ],
            '3' => [
                'title' => 'Prochain événement',
                'description' => 'Rejoignez-nous pour le prochain événement Expanded !',
            ],
        ]);
        $layout->addTranslation($translationFr);
        $manager->persist($translationFr);
    }

    /**
     * Content-channel homepage, same block sequence as production:
     * rich text, carousel, latest news, rich text.
     *
     * @see docs/models/homepage.md
     */
    private function createContentHomepageFixtures(ObjectManager $manager, Channel $contentChannel): void
    {
        $slide = static fn (string $text, string $link): array => [
            'image' => 'https://placehold.co/1200x400/14213d/fca311?text='.rawurlencode($text),
            'alt' => $text,
            'link' => $link,
            'startAt' => null,
            'endAt' => null,
        ];

        $layout = new HomepageLayout();
        $layout->setIsPublished(true);
        $layout->setChannel($contentChannel);
        $layout->setBlocks([
            $this->homepageBlock('richText'),
            $this->homepageBlock('carousel', ['items' => [
                $slide('Regidrago, the deck to beat', '/en/archetypes/regidrago'),
                $slide('The banned list, explained', '/en/banned-cards'),
                $slide('Format staples', '/en/staple-cards'),
            ]]),
            $this->homepageBlock('latestPages', ['categorySlug' => 'news', 'limit' => 5]),
            $this->homepageBlock('richText'),
        ]);
        $manager->persist($layout);

        foreach ([
            'en' => [
                "## Pokémon TCG Expanded, archetype by archetype\n\nDeck lists, variants, the banned list and the cards every deck plays.",
                'New to the format? Start with [What is Expanded?](/en/pages/what-is-expanded).',
            ],
            'fr' => [
                "## Pokémon TCG Expanded, archétype par archétype\n\nDes deck lists, des variantes, la liste des bannies et les cartes que tous les decks jouent.",
                'Tu découvres le format ? Commence par [C\'est quoi, l\'Expanded ?](/fr/pages/what-is-expanded).',
            ],
        ] as $locale => [$intro, $outro]) {
            $translation = new HomepageLayoutTranslation();
            $translation->setHomepageLayout($layout);
            $translation->setLocale($locale);
            $translation->setBlockTranslations(['0' => ['content' => $intro], '3' => ['content' => $outro]]);
            $layout->addTranslation($translation);
            $manager->persist($translation);
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function homepageBlock(string $type, array $data = []): array
    {
        return ['type' => $type, 'columnWidth' => null, 'cssClasses' => null, 'startAt' => null, 'endAt' => null] + $data;
    }

    private function createTcgdexSetMappings(ObjectManager $manager): void
    {
        /** @var array<string, string> $mappings */
        $mappings = require __DIR__.'/data/tcgdex_set_mappings.php';

        foreach ($mappings as $tcgdexSetId => $ptcgCode) {
            $manager->persist(new TcgdexSetMapping($tcgdexSetId, $ptcgCode));
        }
    }

    /**
     * @return array{0: Channel, 1: Channel} [appChannel, contentChannel]
     *
     * @see docs/features.md F18.1 — Channel entity and database schema
     */
    private function createChannels(ObjectManager $manager): array
    {
        $appChannel = (new Channel())
            ->setCode('app')
            ->setDomain('expandeddecks.wip')
            ->setEnableDecks(true)
            ->setEnableRegister(true)
            ->setEnableEvents(true)
            ->setEnableBorrows(true)
            ->setEnableArchetypes(false)
            ->setLocales(['en', 'fr']);

        $contentChannel = (new Channel())
            ->setCode('content')
            ->setDomain('dowsingmachine.wip')
            ->setEnableDecks(false)
            ->setEnableRegister(false)
            ->setEnableEvents(false)
            ->setEnableBorrows(false)
            ->setEnableArchetypes(true)
            ->setEnableBannedCards(true)
            ->setEnableStaples(true)
            ->setThemeName('expandedtalks')
            // English-only content for now; add 'fr' once French translations exist (F19.4).
            ->setLocales(['en'])
            // French is being prepared by translators: draft locale (F9.16),
            // browsable only by translators/moderators/admins until published.
            ->setDraftLocales(['fr'])
            // org_logo + org_same_as feed the Organization publisher JSON-LD (F19.8).
            // meta_description is the per-channel default <meta name="description">
            // when a page supplies none (F19.7). The app channel intentionally
            // omits it so it falls through to the translatable site default.
            ->setParameters([
                'brand_name' => 'Dowsing Machine',
                'org_logo' => '/build/images/themes/expandedtalks/apple_touch_icon.png',
                'org_same_as' => "https://bsky.app/profile/example.bsky.social\nhttps://discord.gg/example\nhttps://github.com/jbourdin/dowsingMachine",
                'meta_description' => 'Pokémon TCG Expanded strategy: deck archetypes, the banned list, staple cards, and competitive deck lists.',
            ]);

        $manager->persist($appChannel);
        $manager->persist($contentChannel);
        $manager->flush();

        return [$appChannel, $contentChannel];
    }
}
