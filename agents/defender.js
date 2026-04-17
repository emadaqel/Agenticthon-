export function defenderAgent(response) {
  const isUnsafe = response.toLowerCase().includes("attack");

  if (isUnsafe) {
    return {
      action: "block",
      reason: "Unsafe content detected"
    };
  }

  return {
    action: "allow",
    reason: "Response is safe"
  };
}
