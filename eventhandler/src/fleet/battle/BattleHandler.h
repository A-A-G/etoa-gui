
#ifndef __BATTLEHANDLER__
#define __BATTLEHANDLER__

/**
* Handles battles....
*
* \author Stephan Vock <glaubinix@etoa.ch>
*/

#include <string>

class Fleet;
class Entity;
class Log;

class BattleHandler
{
  public:
    BattleHandler() = default;
    ~BattleHandler() {};
    void battle(Fleet* fleet, Entity* entity, Log* log, bool ratingEffect = true);
    bool getReturnFleet() const;
    bool getBattleResult() const;

  private:
    int getUser(std::string& users, size_t& found) const;
    short _battleResult = 4;
    bool _returnFleet = true;

};
#endif
