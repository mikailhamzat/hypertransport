<?php

use App\Enums\Status;

describe('Status Enum', function () {
    it('has correct values', function () {
        expect(Status::SCHEDULED->value)->toBe('scheduled');
        expect(Status::ACTIVE->value)->toBe('active');
        expect(Status::COMPLETED->value)->toBe('completed');
        expect(Status::CANCELLED->value)->toBe('cancelled');
    });

    it('has correct labels', function () {
        expect(Status::SCHEDULED->getLabel())->toBe('Scheduled');
        expect(Status::ACTIVE->getLabel())->toBe('Active');
        expect(Status::COMPLETED->getLabel())->toBe('Completed');
        expect(Status::CANCELLED->getLabel())->toBe('Cancelled');
    });

    it('has correct colors', function () {
        expect(Status::SCHEDULED->getColor())->toBe('gray');
        expect(Status::ACTIVE->getColor())->toBe('primary');
        expect(Status::COMPLETED->getColor())->toBe('success');
        expect(Status::CANCELLED->getColor())->toBe('danger');
    });

    it('implements HasLabel interface', function () {
        expect(Status::SCHEDULED)->toBeInstanceOf(\Filament\Support\Contracts\HasLabel::class);
    });

    it('implements HasColor interface', function () {
        expect(Status::SCHEDULED)->toBeInstanceOf(\Filament\Support\Contracts\HasColor::class);
    });

    it('can be used in match expressions', function () {
        $status = Status::ACTIVE;
        
        $result = match ($status) {
            Status::SCHEDULED => 'not started',
            Status::ACTIVE => 'in progress',
            Status::COMPLETED => 'finished',
            Status::CANCELLED => 'cancelled',
        };
        
        expect($result)->toBe('in progress');
    });

    it('can be created from string values', function () {
        expect(Status::from('scheduled'))->toBe(Status::SCHEDULED);
        expect(Status::from('active'))->toBe(Status::ACTIVE);
        expect(Status::from('completed'))->toBe(Status::COMPLETED);
        expect(Status::from('cancelled'))->toBe(Status::CANCELLED);
    });

    it('can try from string values', function () {
        expect(Status::tryFrom('scheduled'))->toBe(Status::SCHEDULED);
        expect(Status::tryFrom('invalid'))->toBeNull();
    });

    it('returns all cases', function () {
        $cases = Status::cases();
        
        expect($cases)->toHaveCount(4);
        expect($cases)->toContain(Status::SCHEDULED, Status::ACTIVE, Status::COMPLETED, Status::CANCELLED);
    });
});
