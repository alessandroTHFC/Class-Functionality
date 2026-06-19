<?php

declare(strict_types=1);

// Tenant routing via domain/subdomain is not used in this application.
// Tenant context is resolved from the authenticated user's tenant_id via
// the InitialiseTenantFromUser middleware on all API routes.
// All application routes are defined in routes/api.php.
