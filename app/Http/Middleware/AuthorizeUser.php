<?php

namespace App\Http\Middleware;

use App\Helper\V1\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeUser
{
    private const ALLOWED_ROLES = ['admin', 'writer', 'reader'];

    /**
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles  e.g. authorize:admin  or  authorize:admin,writer
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if ($roles === []) {
            return ApiResponse::error('No roles specified for authorization.', 500);
        }

        $user = $request->user();

        if (! $user) {
            return ApiResponse::unauthorized('You need to be authenticated.');
        }

        $roles = $this->normalizeRoles($roles);

        if ($roles === null) {
            return ApiResponse::error('Invalid role specified for authorization.', 500);
        }

        if (! in_array($user->role, $roles, true)) {
            return ApiResponse::forbidden('You do not have permission to access this resource.');
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $roles
     * @return array<int, string>|null
     */
    private function normalizeRoles(array $roles): ?array
    {
        $normalized = [];

        foreach ($roles as $role) {
            $role = strtolower(trim($role));

            if (! in_array($role, self::ALLOWED_ROLES, true)) {
                return null;
            }

            $normalized[] = $role;
        }

        return array_values(array_unique($normalized));
    }
}
